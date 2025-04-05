from utils.conn_psql import PostgreSQLDatabase

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

class Team:
    def __init__(self, owner_id, season):
        self.owner_id = owner_id
        self.season = season
        self.team_name = self.get_team_name_from_db()
        self.roster = self.get_roster()
        self.active_players = self.get_active_players()

        self.total_points = self.get_total_points()

        self.set_money()

    def set_money(self):
        # Fetch the initial bank and salary amounts for this owner and season from the database
        query = """
            SELECT bank_init, bank_current, salary_init, salary_current 
            FROM owner_x_season 
            WHERE id = %s AND season = %s
        """
        results = fetch_results(query, (self.owner_id, self.season))
        if results and results[0]:
            row = results[0]
            self.bank_init = row[0]
            self.bank_current = row[1]
            self.salary_init = row[2]
            self.salary_current = row[3]
        else:
            # Set to None or some default values if the query doesn't return data
            self.bank_init = None
            self.bank_current = None
            self.salary_init = None
            self.salary_current = None

    def get_team_name_from_db(self):
        # Fetch the team name for this owner and season from the database
        query = "SELECT team_name FROM owner_x_season WHERE id = %s AND season = %s"
        results = fetch_results(query, (self.owner_id, self.season))
        if results and results[0][0]:
            return results[0][0]
        else:
            return None
    
    def get_roster(self):
        # Fetch the team roster from the database
        query = """
            SELECT player_id, start_date, bench_date, prev_points, points, player_display_name
            FROM owner_x_player_detail
            WHERE owner_id = %s AND season = %s
        """
        results = fetch_results(query, (self.owner_id, self.season))
        # Return a dictionary mapping player_id to player details, for example:
        roster = {
            row[0]: {
                'start_date': row[1],
                'bench_date': row[2],
                'prev_points': row[3],
                'points': row[4],
                'player_display_name': row[5]
            } for row in results
        } if results else {}
        return roster
    
    def get_active_players(self):
        query = """
            SELECT *
            FROM (
                SELECT 
                    player_id,
                    pos,
                    salary,
                    start_date,
                    bench_date,
                    prev_points,
                    points,
                    player_display_name,
                    team,
                    yesterday,
                    recent,
                    val,
                    CASE pos
                        WHEN 'C'  THEN 1
                        WHEN '1B' THEN 2
                        WHEN '2B' THEN 3
                        WHEN '3B' THEN 4
                        WHEN 'SS' THEN 5
                        WHEN 'OF' THEN 6
                        WHEN 'SP' THEN 7
                        WHEN 'RP' THEN 8
                        ELSE 9
                    END AS pos_order
                FROM owner_x_player_detail
                WHERE owner_id = %s AND season = %s AND bench_date = 0
            ) AS sub
            ORDER BY
                pos_order,
                CASE WHEN pos IN ('OF', 'SP') THEN salary END DESC;
        """
        results = fetch_results(query, (self.owner_id, self.season))
        columns = ['player_id', 'pos', 'salary', 'start_date', 'bench_date', 'prev_points', 'points', 'player_display_name', 'team', 'yesterday', 'recent', 'val', 'pos_order']
        players = {
            row[0]: {col: val for col, val in zip(columns[1:], row[1:])}
            for row in results
        } if results else {}
        return players

    def get_benched_players(self):
        query = """
            SELECT 
                player_id, 
                pos, 
                team, 
                salary, 
                points, 
                player_display_name
            FROM owner_x_player_detail
            WHERE owner_id = %s 
            AND season = %s 
            AND bench_date > 0
        """
        results = fetch_results(query, (self.owner_id, self.season))
        benched_players = {
            row[0]: {
                'pos': row[1],
                'team': row[2],
                'salary': row[3],
                'points': row[4],
                'player_display_name': row[8]
            } for row in results
        } if results else {}
        return benched_players

    def get_total_points(self):
        query = """
            SELECT points FROM owner_x_season WHERE id = %s AND season = %s
        """
        results = fetch_results(query, (self.owner_id, self.season))
        if results and results[0][0] is not None:
            return results[0][0]
        else:
            print(f"No points data found for owner {self.owner_id} in season {self.season}.")
            return 0