# generate_home_page.py
from jinja2 import Environment, FileSystemLoader
from utils.conn_psql import fetch_results
from owner import Owner
from team import Team

import boto3
import os

##########################################################

HOME_PATH = os.getenv('home_path')

##########################################################
# AWS S3 Configuration

s3 = boto3.client('s3')

local_file = f'{HOME_PATH}/html/index.html'  # Path to your local file
bucket_name = 'baseball.tomgsmith.com'
s3_key = 'temp/index.html'  # This can include a folder path if needed

##########################################################
# Jinja2 Configuration

env = Environment(loader=FileSystemLoader(f'{HOME_PATH}/templates'))
template = env.get_template('home.html')

##########################################################

def generate_home_page(season, updated_at):
    """
    Generate the home page HTML for the given season and updated_at timestamp.
    """
    query = """
        SELECT id 
        FROM owner_x_season 
        WHERE season = %s 
        ORDER BY place ASC
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
        'updated_at': updated_at
    }

    html_output = template.render(context)

    os.makedirs(os.path.dirname(local_file), exist_ok=True)

    with open(local_file, "w", encoding="utf-8") as file:
        file.write(html_output)

    s3.upload_file(local_file, bucket_name, s3_key, ExtraArgs={'ACL': 'public-read', 'ContentType': 'text/html'})

def generate_page(season, section):

    if section == "trade":

        base_url = os.getenv('base_url')

        print(f"Generating {section} page for season {season}...")

        trade_template = env.get_template('trade.html')

        rendered_trade_html = trade_template.render({
            'base_url': base_url,
            'season': season
        })

        trade_html = f"{HOME_PATH}/static/trade.html"

        os.makedirs(os.path.dirname(trade_html), exist_ok=True)

        with open(trade_html, "w", encoding="utf-8") as file:
            file.write(rendered_trade_html)

print("trade.html generated successfully.")

def ordinal_place(n):
    """
    Convert an integer n into a string like "1st place", "2nd place", etc.
    """
    n = int(n)  # Ensure n is an integer
    # Handle special case for 11, 12, and 13
    if 11 <= (n % 100) <= 13:
        suffix = "th"
    else:
        last_digit = n % 10
        if last_digit == 1:
            suffix = "st"
        elif last_digit == 2:
            suffix = "nd"
        elif last_digit == 3:
            suffix = "rd"
        else:
            suffix = "th"
    return f"{n}{suffix}"
