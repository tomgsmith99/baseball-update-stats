from utils.conn_psql import PostgreSQLDatabase, fetch_results

################################################

class Team:
    def __init__(self, owner_id, season):
        self.owner_id = owner_id
        self.season = season
        self.roster = self.get_roster()
        self.active_players = self.get_active_players()

        self._init()

    def _init(self):
        query = """
            SELECT team_name, points, recent, yesterday, salary_init, salary_current, bank_init, bank_current, place, first_name, last_name, suffix, nickname FROM owner_x_season_detail
            WHERE id = %s AND season = %s
        """
        results = fetch_results(query, (self.owner_id, self.season))
        if results and results[0]:  # Check if results are not empty
            row = results[0]
            self.team_name = row[0]
            self.total_points = row[1]
            self.recent = row[2]
            self.yesterday = row[3]
            self.salary_init = row[4]
            self.salary_current = row[5]
            self.bank_init = row[6]
            self.bank_current = row[7]
            self.place = row[8]
            self.first_name = row[9]
            self.last_name = row[10]
            self.suffix = row[11]
            self.nickname = row[12]
    
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

        if results:
            print (self.nickname)

            print("Benched Players")
            print(results)

        benched_players = {
            row[0]: {
                'pos': row[1],
                'team': row[2],
                'salary': row[3],
                'points': row[4],
                'player_display_name': row[5]
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

    def to_dict(self):
        """
        Return a dictionary representation of the team with all relevant fields.
        """
        return {
            "owner_id": self.owner_id,
            "season": self.season,
            "team_name": self.team_name,
            "total_points": self.total_points,
            "recent": self.recent,
            "yesterday": self.yesterday,
            "salary_init": self.salary_init,
            "salary_current": self.salary_current,
            "bank_init": self.bank_init,
            "bank_current": self.bank_current,
            "place": self.place,
            "first_name": self.first_name,
            "last_name": self.last_name,
            "suffix": self.suffix,
            "nickname": self.nickname,
            "roster": self.roster,
            "active_players": self.active_players,
            "benched_players": self.get_benched_players()  # You may cache this if needed
        }