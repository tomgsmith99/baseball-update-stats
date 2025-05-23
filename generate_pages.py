from datetime import datetime
from dotenv import load_dotenv
from jinja2 import Environment, FileSystemLoader
from zoneinfo import ZoneInfo

import boto3
import botocore
import os

##########################################################

from ordinal import ordinal_place
from team import Team
from utils.conn_psql import fetch_results

load_dotenv()

##########################################################

BUCKET_NAME = 'baseball.tomgsmith.com'

HOME_PATH = os.getenv('home_path')
HTML_PATH = f'{HOME_PATH}/html'

WEB_HOME = os.getenv('web_home')

MAKE_A_TRADE_URL = os.getenv('make_a_trade_url')

##########################################################
# AWS S3 Configuration

s3 = boto3.client('s3')

##########################################################
# Jinja2 Configuration

env = Environment(loader=FileSystemLoader(f'{HOME_PATH}/templates'))
template = env.get_template('home.html')

##########################################################

def generate_page(season, section):

    eastern = ZoneInfo("America/New_York")

    generated_at = datetime.now(tz=eastern).strftime("%A, %B %d, %I:%M %p")

    if section == "css":

        print("Generating CSS...")

        local_path = f'html/baseball.css'

        upload_html_to_s3(local_path, 'baseball.css')

    if section == "home":

        query = """
            SELECT id, nickname AS display_name, recent, yesterday, picture FROM owner_x_season_detail WHERE season = %s ORDER BY place ASC
        """

        owners = fetch_results(query, (season,))

        if not owners:
            print(f"No owners found for season {season}.")
            exit()

        total_owners = len(owners)

        print(f"{total_owners} owners with teams in season {season}:")
        print("\n*******************************************************")

        teams_context = []

        for count, row in enumerate(owners, start=1):
            owner_id = row[0]
            team = Team(owner_id, season)
            place = team.place
            active_players = team.get_active_players()
            benched_players = team.get_benched_players()

            teams_context.append({
                'team': team,
                'place': ordinal_place(place),
                'active_players': active_players,
                'benched_players': benched_players,
                'owner_picture': row[4],
            })

            print(f"Processed {count}/{total_owners}: {team.nickname} - {team.team_name}")

        # get players

        query = """
            SELECT id, pos, display_name, team, points, p_type, salary, val, yesterday, recent
            FROM player_x_season_detail
            WHERE season = %s
        """

        players = fetch_results(query, (season,), True)

        hottest_owners = sorted(owners, key=lambda x: x['recent'], reverse=True)[:5]
        yesterday_owners = sorted(owners, key=lambda x: x['yesterday'], reverse=True)[:5]
        hottest_players = sorted(players, key=lambda x: x['recent'], reverse=True)[:5]
        yesterday_players = sorted(players, key=lambda x: x['yesterday'], reverse=True)[:5]
        most_valuable_players = sorted(players, key=lambda x: x['val'], reverse=True)[:5]

        leaderboards = [
            {"id": "lboard_owners_hottest", "title": "Owners - Hottest", "items": hottest_owners, "item_type": "owner", "field": "recent"},
            {"id": "lboard_owners_yesterday", "title": "Owners - Yesterday", "items": yesterday_owners, "item_type": "owner", "field": "yesterday"},
            {"id": "lboard_players_yesterday", "title": "Players - Yesterday", "items": yesterday_players, "item_type": "player", "field": "yesterday"},
            {"id": "lboard_players_hottest", "title": "Players - Hottest", "items": hottest_players, "item_type": "player", "field": "recent"},
            {"id": "lboard_players_val", "title": "Players - Most Valuable", "items": most_valuable_players, "item_type": "player", "field": "val"}
        ]

        context = {
            'teams': teams_context,
            'generated_at': generated_at,
            'base_url': os.getenv('heroku_url'),
            'active_page': 'home',
            'web_home': WEB_HOME,
            'owners_by_name': sorted(owners, key=lambda x: x['display_name']),
            'season': season,
            'players': players,
            'leaderboards': leaderboards
        }

        template = env.get_template('home.html')

        local_path = f'index.html'

        write_html(template, context, local_path)

    if section == "players":

        query = """
            SELECT id, pos, display_name, team, points, salary, val, yesterday, recent
            FROM player_x_season_detail
            WHERE season = %s
            ORDER BY last_name, first_name
        """

        results = fetch_results(query, (season,), True)

        if not results:
            print(f"No players found for season {season}.")
            exit()

        print(f"{len(results)} players found for season {season}.")

        context = {
            'season': season,
            'players': results,
            'generated_at': generated_at,
            'active_page': 'players',
            'web_home': WEB_HOME
        }

        template = env.get_template('players.html')

        local_path = f'seasons/{season}/players/index.html'

        write_html(template, context, local_path)

    if section == "trades":

        query = """
            SELECT owner_id, owner_nickname, dropped_player_id, dropped_player_name, added_player_id, added_player_name, stamp, stamp_old
            FROM trades_detail
            WHERE season = %s
            ORDER BY stamp DESC
        """

        results = fetch_results(query, (season,), True)

        for row in results:

            dt = row['stamp']

            month = dt.strftime("%B")
            day = ordinal_place(dt.day)

            row['stamp'] = f"{month} {day}"
        
        context = {
            'season': season,
            'trades': results,
            'generated_at': generated_at,
            'web_home': WEB_HOME,
            'active_page': 'trades',
            'make_a_trade_url': MAKE_A_TRADE_URL
        }

        template = env.get_template('trades.html')

        local_path = f'seasons/{season}/trades/index.html'

        write_html(template, context, local_path)

        # Generate the "make a trade" page

        base_url = os.getenv('heroku_url')

        print(f"Generating {section} page for season {season}...")

        context = {
            'base_url': base_url,
            'season': season,
            'generated_at': generated_at,
            'web_home': os.getenv('s3_web_home'),
            'active_page': 'trades'
        }

        template = env.get_template('trade.html')

        local_path = f'trades/make_a_trade.html'

        write_html(template, context, local_path)

def generate_section(section):

    print(f"Generating {section} section...")

def upload_html_to_s3(local_file, s3_key):

    s3_key = f'temp/{s3_key}'

    try:
        s3.upload_file(
            local_file,
            BUCKET_NAME,
            s3_key,
            ExtraArgs={
                'ACL': 'public-read',
                'ContentType': 'text/html',
                'CacheControl': 'max-age=0, no-cache, no-store, must-revalidate'
            }
        )

        print("File uploaded successfully.")

    except botocore.exceptions.ClientError as e:
        # Print the error details
        print("❌ File upload failed:", e)
    except Exception as ex:
        print("❌ An unexpected error occurred:", ex)

def write_html(template, context, local_path):

    html = template.render(context)

    full_path = os.path.join(HTML_PATH, local_path)

    os.makedirs(os.path.dirname(full_path), exist_ok=True)

    with open(full_path, "w", encoding="utf-8") as file:
        file.write(html)

    upload_html_to_s3(full_path, local_path)