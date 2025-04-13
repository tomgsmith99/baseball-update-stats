import time

from generate_pages import generate_page
from owner import Owner
from player import Player
from utils.conn_psql import execute_query, fetch_results

##################################
THIS_SEASON = 2025
SALARY_CAP = 13000

PAUSE_LENGTH = 0.2
##################################

def update_owner_x_player_table(season):

    query = """
        SELECT DISTINCT oxp.player_id, p.last_name, p.first_name, pxs.points
        FROM owner_x_player AS oxp
        LEFT JOIN player AS p ON oxp.player_id = p.id
        LEFT JOIN player_x_season AS pxs ON oxp.player_id = pxs.id AND oxp.season = pxs.season
        WHERE oxp.season = %s
        ORDER BY p.last_name, p.first_name;
    """

    values = (season,)

    results = fetch_results(query, values, True)

    i = 0

    player_count = len(results)

    for player in results:

        i += 1

        print(f"Updating player {i} of {player_count}...")

        player_id = player['player_id']

        points = player['points']

        print(f"Updating owner_x_player table for player {player['last_name']}, {player['first_name']}...")

        query = """
            UPDATE owner_x_player
            SET points = %s
            WHERE player_id = %s AND season = %s AND start_date = 0 AND bench_date = 0;
        """
        values = (points, player_id, season)

        execute_query(query, values)

        query = """
            UPDATE owner_x_player
            SET points = %s - prev_points
            WHERE player_id = %s AND season = %s AND start_date > 0 AND bench_date = 0;
        """
        values = (points, player_id, season)

        execute_query(query, values)

def update_owners(season):

    query = """
        SELECT id FROM owner WHERE id IN (SELECT id FROM owner_x_season WHERE season = %s) ORDER BY owner.last_name, owner.first_name, owner.suffix;
    """

    results = fetch_results(query, (season,))

    owner_count = len(results)

    if results:
        print(f"{owner_count} owners found for season {season}.")
    else:
        print(f"No owners found for season {season}.")
        exit()

    i = 0

    for row in results:

        i += 1

        owner = Owner(row[0])

        print(f"Updating owner {i} of {owner_count}...")

        print("\n#############################")

        print(owner.nickname)

        owner.update_stats(season)

def update_place(season):

    query = """
        SELECT id, points
        FROM owner_x_points_x_day_x_season
        WHERE season = %s
        AND day = (
            SELECT MAX(day)
            FROM owner_x_points_x_day_x_season
            WHERE season = %s
        )
        ORDER BY points DESC;
    """

    results = fetch_results(query, (season, season))

    if results:
        print(f"{len(results)} owners found for season {season}.")
    else:
        print(f"No owners found for season {season}.")
        exit()

    i = 0

    for row in results:

        i += 1

        owner = Owner(row[0])

        print(f"Updating place for owner {i} of {len(results)}...")

        print("\n#############################")

        print(owner.nickname)

        owner.update_place(i, season)

def update_players(season):

    query = """
        SELECT id FROM player WHERE id IN (SELECT id FROM player_x_season WHERE season = %s) ORDER BY player.last_name, player.first_name;
    """

    results = fetch_results(query, (season,))

    player_count = len(results)

    if results:
        print(f"{player_count} players found for season {season}.")
    else:
        print(f"No players found for season {season}.")
        exit()

    i = 1

    for row in results:

        print(f"Updating player {i} of {player_count}...")

        i += 1

        player = Player(row[0])

        print("\n#############################")

        print(player.display_name)

        player.update_stats(season)

        print("pausing...")

        time.sleep(PAUSE_LENGTH)

######################################################
# ✅ MAIN FUNCTION

def main():

    print("Updating stats...")

    update_players(THIS_SEASON)

    update_owner_x_player_table(THIS_SEASON)

    update_owners(THIS_SEASON)

    update_place(THIS_SEASON)

    print("Generating home page...")
    generate_page(THIS_SEASON, "home")

    print("Generating trades page...")
    generate_page(THIS_SEASON, "make_a_trade")

    print("Generating players page...")
    generate_page(THIS_SEASON, "players")

    #################################################################

    exit()

if __name__ == "__main__":
    main()
