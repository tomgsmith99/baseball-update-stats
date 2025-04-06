from dotenv import load_dotenv
from flask import Flask, request, session, jsonify
from flask_cors import CORS

from utils.conn_psql import PostgreSQLDatabase
from team import Team

import json
import os

###############################################

load_dotenv()

SEASON = 2025

###############################################

def fetch_results(query, values=()):
    """Fetch results from the database."""
    with PostgreSQLDatabase() as psql_db:
        try:
            psql_db.cursor.execute(query, values)
            return psql_db.cursor.fetchall()
        except Exception as e:
            print(f"❌ Database Query Error: {e}")
            return None

###############################################

app = Flask(__name__)
CORS(app)  # This will enable CORS for all routes
# CORS(app, supports_credentials=True)
CORS(app, supports_credentials=True, resources={r"/*": {"origins": "http://127.0.0.1:5001"}})
app.secret_key = os.getenv('secret_key')

# app.config['SESSION_COOKIE_NAME'] = 'your_session'
# app.config['SESSION_COOKIE_DOMAIN'] = '127.0.0.1'

###############################################

@app.route("/")
def hello():
    return "Hello, World!"

@app.route('/favicon.ico')
def favicon():
    return '', 204

###############################################
# POST calls

@app.route("/eval_password", methods=["POST"])
def eval_password():
    # Retrieve the password from form data
    password = request.form.get('password')

    print(f"Password: {password}")

    if password.lower() in [os.getenv('family_password01'), os.getenv('family_password02')]:

        print("Password is correct")

        session['logged_in'] = True
        return "authenticated"
    else:

        print("Password is incorrect")

        session['logged_in'] = False
        return "unauthenticated"

@app.route("/evaluate_owner", methods=["POST"])
def evaluate_owner():

    if not session.get('logged_in'):
        return jsonify({"error": "Unauthorized"}), 401
    
    owner_id = request.form.get('owner_id')
    
    team = Team(owner_id, season=SEASON)

    print(team.owner_id)

    return team
    

###############################################
# GET calls

@app.route('/get_owners', methods=['GET'])
def get_owners():

    print("Fetching owners...")
    # Check if the user is logged in

    print("the session is:")
    print(session)

    if not session.get('logged_in'):
        return jsonify({"error": "Unauthorized"}), 401

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