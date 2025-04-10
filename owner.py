from utils.conn_psql import PostgreSQLDatabase

import datetime

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

class Owner:
    def __init__(self, id):
        self.id = id
        self.nickname = self.get_nickname()
        self.most_recent_season = self.get_most_recent_season()
        self.default_team_name = self.get_default_team_name()

    def get_nickname(self):
        """Retrieve the owner's nickname from the database."""
        query = "SELECT nickname FROM owner WHERE id = %s"
        results = fetch_results(query, (self.id,))
        if results:
            return results[0][0]
        else:
            print(f"❌ No owner found with ID {self.id}.")
            return None

    def get_most_recent_season(self):
        """Retrieve the most recent season for this owner."""
        query = "SELECT MAX(season) FROM owner_x_season WHERE id = %s"
        results = fetch_results(query, (self.id,))
        if results and results[0][0] is not None:
            season = results[0][0]
            return season
        else:
            print(f"No seasons found for owner {self.id}")
            return 0

    def get_current_points(self, season):

        query = """
            SELECT SUM(points) FROM owner_x_player WHERE owner_id = %s AND season = %s
        """
        results = fetch_results(query, (self.id, season))
        if results and results[0][0] is not None:
            points = results[0][0]
            print(f"Total points for owner {self.id} in season {season}: {points}")
            return points
        else:
            print(f"No points data found for owner {self.id} in season {season}.")
            return 0

    def get_default_team_name(self):
        """Retrieve the default team name from the owner's most recent season."""
        query = "SELECT team_name FROM owner_x_season WHERE id = %s AND season = %s"
        results = fetch_results(query, (self.id, self.most_recent_season))
        if results and results[0][0] is not None:
            return results[0][0]
        else:
            print(f"No default team name found for owner {self.id}.")
            return None

    def get_place(self, season):
        """Retrieve the owner's place in the league for the given season."""
        query = "SELECT place FROM owner_x_season WHERE id = %s AND season = %s"
        results = fetch_results(query, (self.id, season))
        if results and results[0][0] is not None:
            return results[0][0]
        else:
            print(f"No place found for owner {self.id} in season {season}.")
            return None

    def get_roster(self, season):
        query = """
            SELECT player_id, start_date, bench_date, prev_points, points, player_display_name
            FROM owner_x_player_detail
            WHERE owner_id = %s AND season = %s
        """
        results = fetch_results(query, (self.id, season))
        roster = {row[0]: {
                        'start_date': row[1],
                        'bench_date': row[2],
                        'prev_points': row[3],
                        'points': row[4],
                        'player_display_name': row[5]
                } for row in results} if results else {}
        return roster

    def get_salary_total(self, roster, season):
        """Calculate the total salary for the owner's roster."""
        query = """
            SELECT SUM(salary)
            FROM player_x_season
            WHERE id IN %s AND season = %s
        """
        # Convert roster list to a tuple for SQL IN clause
        roster_tuple = tuple(roster)
        results = fetch_results(query, (roster_tuple, season))
        if results and results[0][0] is not None:
            return results[0][0]
        else:
            print(f"No salary data found for the roster in season {season}.")
            return 0

    def set_team_name(self, team_name, season):

        """Set the team name for the owner."""
        query = "UPDATE owner_x_season SET team_name = %s WHERE id = %s AND season = %s"
        values = (team_name, self.id, season)
        with PostgreSQLDatabase() as psql_db:
            try:
                psql_db.cursor.execute(query, values)
                psql_db.connection.commit()
                print(f"Team name updated to {team_name} for owner {self.id}.")
            except Exception as e:
                print(f"❌ Error updating team name: {e}")

    def update_place(self, place, season):
        """Update the owner's place in the league for the given season."""
        query = "UPDATE owner_x_season SET place = %s WHERE id = %s AND season = %s"
        values = (place, self.id, season)
        with PostgreSQLDatabase() as psql_db:
            try:
                psql_db.cursor.execute(query, values)
                psql_db.connection.commit()
                print(f"Place updated to {place} for owner {self.id} in season {season}.")
            except Exception as e:
                print(f"❌ Error updating place: {e}")

    def update_stats(self, season):

        # Calculate points for benched players here

        points = self.get_current_points(season)

        current_day_of_year = datetime.datetime.now().timetuple().tm_yday

        current_timestamp = datetime.datetime.now()

        query = """
            INSERT INTO owner_x_points_x_day_x_season (points, id, day, season, last_updated)
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

        query = """
            UPDATE owner_x_season set points = %s WHERE season = %s AND id = %s;
        """
        values = (points, season, self.id)
        with PostgreSQLDatabase() as db:
            db.execute_query(query, values)
            print(f"Points updated for owner {self.id} in season {season} in owner_x_season table.")
