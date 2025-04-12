from dotenv import load_dotenv
from flask import Flask, request, session, jsonify
from flask_cors import CORS

from utils.conn_psql import execute_query, fetch_results
from team import Team
from player import Player

import datetime
import os

###############################################

load_dotenv()

SEASON = 2025

BASE_URL = os.getenv('base_url')

###############################################

app = Flask(__name__)
CORS(app)  # This will enable CORS for all routes
CORS(app, supports_credentials=True, resources={r"/*": {"origins": [
    BASE_URL
]}})
app.secret_key = os.getenv('secret_key')

###############################################

@app.route("/")
def hello():
    return "Hello, World!"

@app.route('/favicon.ico')
def favicon():
    return '', 204

###############################################
# POST calls

@app.route("/commit_trade", methods=["POST"])
def commit_trade():

    print("Committing trade...")

    if not session.get('logged_in'): return jsonify({"error": "Unauthorized"}), 401

    now = datetime.datetime.now()

    day_of_year = now.timetuple().tm_yday

    owner_id = session.get('owner_id')

    # Get the added and dropped player IDs from the session
    added_player_id = session.get('added_player_id')
    added_player_points = session.get('added_player_points')

    dropped_player_id = session.get('dropped_player_id')
    dropped_player_points = session.get('dropped_player_points')

    # Update the status of the dropped player in the owner_x_player table
    query = """
        UPDATE owner_x_player 
        SET bench_date = %s, points = %s
        WHERE season = %s AND owner_id = %s AND player_id = %s;
    """
    values = (day_of_year, dropped_player_points, SEASON, owner_id, dropped_player_id)

    execute_query(query, values)
    
    # Insert the added player into the owner_x_player table
    query = """
        INSERT INTO owner_x_player (season, owner_id, player_id, start_date, prev_points)
        VALUES (%s, %s, %s, %s, %s);
    """
    values = (SEASON, owner_id, added_player_id, day_of_year, added_player_points)

    execute_query(query, values)

    # Update the bank_current for the owner
    query = """
        UPDATE owner_x_season 
        SET bank_current = %s 
        WHERE id = %s AND season = %s;
    """
    values = (session['bank_new'], owner_id, SEASON)

    execute_query(query, values)

    return jsonify({"success": True})

# Evaluate password
@app.route("/eval_password", methods=["POST"])
def eval_password():

    password = request.form.get('password')

    if password.lower() in [os.getenv('family_password01'), os.getenv('family_password02')]:
        session['logged_in'] = True
        return "authenticated"
    else:
        session['logged_in'] = False
        return "unauthenticated"

# Evaluate owner
@app.route("/evaluate_owner", methods=["POST"])
def evaluate_owner():

    if not session.get('logged_in'): return jsonify({"error": "Unauthorized"}), 401

    owner_id = request.form.get('owner_id')

    session['owner_id'] = owner_id
    
    team = Team(owner_id, season=SEASON)

    session['bank_current'] = team.bank_current
    session['nickname'] = team.nickname

    return team.to_dict()

# Evaluate added player
@app.route("/evaluate_added_player", methods=["POST"])
def evaluate_added_player():

    if not session.get('logged_in'): return jsonify({"error": "Unauthorized"}), 401

    added_player_id = request.form.get('player_id')

    player = Player(added_player_id)

    season_details = player.get_season_details(SEASON)

    session['salary_diff'] = season_details['salary'] - session['dropped_player_salary']

    if session['salary_diff'] > 0:
        session['bank_new'] = session['bank_current'] - session['salary_diff']
    else:
        session['bank_new'] = session['bank_current']

    session['added_player_id'] = added_player_id
    session['added_player_points'] = season_details['points']
    session['added_player_team'] = season_details['team']
    session['added_player_display_name'] = player.display_name
    session['added_player_salary'] = season_details['salary']

    return jsonify(dict(session))

# Evaluate dropped player
@app.route("/evaluate_dropped_player", methods=["POST"])
def evaluate_dropped_player():

    if not session.get('logged_in'): return jsonify({"error": "Unauthorized"}), 401

    player_id = request.form.get('player_id')

    player = Player(player_id)

    season_details = player.get_season_details(SEASON)

    session['pos'] = season_details['pos']
    session['dropped_player_id'] = player_id
    session['dropped_player_display_name'] = player.display_name
    session['dropped_player_points'] = season_details['points']
    session['dropped_player_team'] = season_details['team']
    session['dropped_player_salary'] = season_details['salary']

    max_salary = session['bank_current'] + session['dropped_player_salary']

    # get available players
    query = """
        SELECT id, pos, last_name, first_name, team, points, salary
        FROM player_x_season_detail
        WHERE season = %s AND salary <= %s AND pos = %s
        ORDER BY last_name, first_name;"""
    
    values = (SEASON, max_salary, session['pos'])

    results = fetch_results(query, values, True)

    if not results:
        return jsonify({"error": "No players found"}), 404

    return jsonify(results)

###############################################
# GET calls

@app.route('/get_owners', methods=['GET'])
def get_owners():

    print("Fetching owners...")

    if not session.get('logged_in'): return jsonify({"error": "Unauthorized"}), 401

    query = """
        SELECT id, first_name, last_name, suffix 
        FROM owner_x_season_detail 
        WHERE season = %s 
        ORDER BY last_name, first_name, suffix;
    """
    owners = fetch_results(query, (SEASON,))

    return owners

#################################################

if __name__ == "__main__":
    # Run the app on localhost:5000 in debug mode
    app.run(host="0.0.0.0", port=5001, debug=True)