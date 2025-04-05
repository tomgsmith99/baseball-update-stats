import datetime
import json
import os
import re
import requests
import time

from dotenv import load_dotenv
from utils.conn_psql import PostgreSQLDatabase

###############################################

load_dotenv()

GOOGLE_API_KEY = os.getenv("google_api_key")
GOOGLE_CSE_ID = os.getenv("google_cse_id")

MLB_URL = "https://statsapi.mlb.com/api/v1/people/{mlb_id}/stats?stats=season&season={season}"

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

################################################

class Player:
    def __init__(self, id):
        self.id = id

        self._init() # get all attributes from player table

        self.most_recent_season = self.get_most_recent_season()

    def _init(self):
        query = """
            SELECT first_name, last_name, middle_initial, suffix, year_added, espn_id, display_name, p_type, mlb_id FROM player WHERE id = %s;
        """
        results = fetch_results(query, (self.id,))
        if results and results[0]:
            row = results[0]
            self.first_name = row[0]
            self.last_name = row[1]
            self.middle_initial = row[2]
            self.suffix = row[3]
            self.year_added = row[4]
            self.espn_id = row[5]
            self.display_name = row[6]
            self.p_type = row[7]
            self.mlb_id = row[8]
        else:
            # Handle case where player is not found
            print(f"❌ Player with ID {self.id} not found.")
    
    # make this a permanent attribute rather than setting every time
    def get_most_recent_season(self):
        query = """
            SELECT MAX(season) FROM player_x_season WHERE id = %s;
        """
        results = fetch_results(query, (self.id,))
        if results and results[0]:
            return results[0][0]
        else:
            print(f"❌ Max season not found for player ID {self.id}.")
            return None
    
    def get_type(self, season):
        query = """
            SELECT pos FROM player_x_season WHERE id = %s AND season = %s;
        """
        results = fetch_results(query, (self.id, season))
        if results and results[0]:

            if results[0][0] in ["SP", "RP"]:
                return "P"
            else:
                return "B"
        else:
            print(f"❌ Max season not found for player ID {self.id}.")
            exit()
    
    def _google_search(self, query, api_key, cse_id, max_retries=5):
        url = "https://www.googleapis.com/customsearch/v1"
        params = {
            "key": api_key,
            "cx": cse_id,
            "q": query
        }
        delay = 1
        for attempt in range(max_retries):
            response = requests.get(url, params=params)
            if response.status_code == 200:
                return response.json()
            elif response.status_code == 429:
                print("Rate limit exceeded, retrying in", delay, "seconds...")
                time.sleep(delay)
                delay *= 2  # exponential backoff
            else:
                print(f"Error fetching search results: {response.status_code}")
                print("Response headers:", response.headers)
                print("Response:", response.text)
                return None
        print("Exceeded maximum retries.")
        return None

    def _get_json_from_mlb(self, season):

        url = MLB_URL.format(mlb_id = self.mlb_id, season = season)

        if self.p_type == "P":
            url += "&group=pitching"
        elif self.p_type == "B":
            url += "&group=hitting"

        print(url)

        headers = {
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.3"
        }

        response = requests.get(url, headers=headers)

        if response.status_code == 200:
            data = response.json()
            return data
        else:
            print(f"Error fetching MLB data: {response.status_code}")
            print("Response headers:")
            for key, value in response.headers.items():
                print(f"{key}: {value}")
            
            exit()
            return None

    def _get_mlb_id(self):

        query = f"{self.first_name} {self.last_name} mlb"
        results = self._google_search(query, GOOGLE_API_KEY, GOOGLE_CSE_ID)

        if results and "items" in results:

            hit = results["items"][0]

            url = hit.get("link")

            match = re.search(r'(\d+)$', url)

            if match:
                mlb_id = match.group(1)
                print("Extracted player ID:", mlb_id)

                self._set_mlb_id(mlb_id)

                return mlb_id
            else:
                print("mlb id not found in google search results.")

                return 0
        else:
            print("No search results found.")

            return 0

    def _get_salary(self, season):
        query = """
            SELECT salary FROM player_x_season WHERE id = %s AND season = %s;
        """
        results = fetch_results(query, (self.id, season))
        if results and results[0]:
            return results[0][0]
        else:
            print(f"❌ Salary not found for player ID {self.id} in season {season}.")
            return 0
    def _set_mlb_id(self, mlb_id):
        query = """
            UPDATE player SET mlb_id = %s WHERE id = %s;
        """
        values = (mlb_id, self.id)

        with PostgreSQLDatabase() as db:
            db.execute_query(query, values)

    def _calculate_points(self, stats, season):

        current_timestamp = datetime.datetime.now()

        if self.p_type == "B":
            runs = stats['runs']
            rbi = stats['rbi']
            hits = stats['hits']
            walks = stats['baseOnBalls']
            sb = stats['stolenBases']
            doubles = stats['doubles']
            triples = stats['triples']
            hr = stats['homeRuns']
            singles = hits - (doubles + triples + hr)

            points = runs + rbi + walks + (sb * 2) + singles + (2 * doubles) + (3 * triples) + (4 * hr)

            query = """
                UPDATE player_x_season 
                SET points = %s, 
                    runs = %s, 
                    rbi = %s, 
                    hits = %s, 
                    walks = %s, 
                    sb = %s, 
                    doubles = %s, 
                    triples = %s, 
                    hr = %s, 
                    last_updated = %s
                WHERE id = %s 
                AND season = %s;
            """
            values = (points, runs, rbi, hits, walks, sb, doubles, triples, hr, current_timestamp, self.id, season)

            with PostgreSQLDatabase() as db:
                db.execute_query(query, values)
        
        elif self.p_type == "P":

            wins = stats['wins']
            ip = int(float(stats['inningsPitched']))
            k = stats['strikeOuts']
            saves = stats['saves']

            points = (wins * 10) + ip + k + (saves * 10)

            query = """
                UPDATE player_x_season SET points = %s, wins = %s, ip = %s, k = %s, saves = %s, last_updated = %s WHERE id = %s AND season = %s;
            """
            values = (points, wins, ip, k, saves, current_timestamp, self.id, season)
            with PostgreSQLDatabase() as db:
                db.execute_query(query, values)
        else:
            print(f"❌ Player type {self.p_type} not recognized.")
    
            exit()
    
        return points

    def _calculate_value(self, season, current_day_of_year, points):

        with open('seasons.json', 'r', encoding='utf-8') as file:

            seasons = json.load(file)

        season_str = str(season)

        first_day = seasons[season_str]['first_day']

        days = current_day_of_year - first_day

        salary = self._get_salary(season)

        v = int(10000 * (points / days / salary))
        
        return v

    def _calculate_recent_points(self, season, current_day_of_year, period):

        if period == "recent":
            recent_day = current_day_of_year - 5
        else:
            recent_day = current_day_of_year - 1

        total_points_today = self.get_points(season)

        query = """
            SELECT points FROM player_x_points_x_day_x_season WHERE id = %s AND season = %s AND day = %s;
        """
        results = fetch_results(query, (self.id, season, recent_day))
        if results and results[0]:
            recent_points = results[0][0]

            return total_points_today - recent_points

        else:
            print(f"❌ {period} points not found for player ID {self.id} in season {season}.")

            return 0

    def _handle_no_stats(self, season):

        """Handle the case when no stats are found for the player in the given season."""
        print(f"❌ No data found for player ID {self.id} in season {season}.")
        points = 0
        query = """
            UPDATE player_x_season 
            SET points = %s 
            WHERE id = %s AND season = %s;
        """
        values = (points, self.id, season)
        with PostgreSQLDatabase() as db:
            db.execute_query(query, values)

    def _update_player_x_points_x_day_x_season_table(self, points, season, current_day_of_year):

        current_timestamp = datetime.datetime.now()

        query = """
            INSERT INTO player_x_points_x_day_x_season (points, id, day, season, last_updated)
            VALUES (%s, %s, %s, %s, %s)
            ON CONFLICT (id, season, day)
            DO UPDATE SET 
                points = EXCLUDED.points,
                last_updated = EXCLUDED.last_updated;
        """
        values = (points, self.id, current_day_of_year, season, current_timestamp)

        with PostgreSQLDatabase() as db:
            db.execute_query(query, values)
            print(f"Points updated for owner {self.id} in season {season}.")

    def get_points(self, season):
        query = """
            SELECT points FROM player_x_season WHERE id = %s AND season = %s;
        """
        results = fetch_results(query, (self.id, season))
        if results and results[0]:
            return results[0][0]
        else:
            print(f"❌ Points not found for player ID {self.id} in season {season}.")
            return 0

    def update_stats(self, season):

        if not self.mlb_id:
            self.mlb_id = self._get_mlb_id()

        data = self._get_json_from_mlb(season)

        if not data['stats']:
            self._handle_no_stats(season)
            return

        stats = data['stats'][0]['splits'][0]['stat']

        points = self._calculate_points(stats, season)

        current_day_of_year = datetime.datetime.now().timetuple().tm_yday

        self._update_player_x_points_x_day_x_season_table(points, season, current_day_of_year)

        val = self._calculate_value(season, current_day_of_year, points)

        yesterday_points = self._calculate_recent_points(season, current_day_of_year, "yesterday")

        recent_points = self._calculate_recent_points(season, current_day_of_year, "recent")

        query = """
            UPDATE player_x_season
            SET val = %s, 
                recent = %s, 
                yesterday = %s
            WHERE id = %s AND season = %s;
        """
        values = (val, recent_points, yesterday_points, self.id, season)
        with PostgreSQLDatabase() as db:
            db.execute_query(query, values)
