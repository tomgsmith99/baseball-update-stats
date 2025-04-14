from dotenv import load_dotenv
from jinja2 import Environment, FileSystemLoader
from zoneinfo import ZoneInfo

import boto3
import botocore
import datetime
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

    generated_at = datetime.datetime.now(tz=eastern).strftime("%A, %B %d, %I:%M %p")

    if section == "make_a_trade":

        # base_url = os.getenv('base_url')
        base_url = os.getenv('heroku_url')

        print(f"Generating {section} page for season {season}...")

        context = {
            'base_url': base_url,
            'season': season,
            'generated_at': generated_at
        }

        template = env.get_template('trade.html')

        html = template.render(context)

        local_path = f'static/index.html'

        full_path = os.path.join(HOME_PATH, local_path)

        os.makedirs(os.path.dirname(full_path), exist_ok=True)

        with open(full_path, "w", encoding="utf-8") as file:
            file.write(html)
    
    if section == "home":

        query = """
            SELECT id FROM owner_x_season WHERE season = %s ORDER BY place ASC
        """

        results = fetch_results(query, (season,))

        if not results:
            print(f"No owners found for season {season}.")
            exit()

        total_owners = len(results)

        print(f"{total_owners} owners with teams in season {season}:")
        print("\n*******************************************************")

        teams_context = []

        for count, row in enumerate(results, start=1):
            owner_id = row[0]
            team = Team(owner_id, season)
            place = team.place
            active_players = team.get_active_players()
            benched_players = team.get_benched_players()

            teams_context.append({
                'team': team,
                'place': ordinal_place(place),
                'active_players': active_players,
                'benched_players': benched_players
            })

            print(f"Processed {count}/{total_owners}: {team.nickname} - {team.team_name}")

        context = {
            'teams': teams_context,
            'generated_at': generated_at,
            'base_url': os.getenv('heroku_url'),
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
            'generated_at': generated_at
        }

        template = env.get_template('players.html')

        local_path = f'seasons/{season}/players/index.html'

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