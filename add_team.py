from prompt_toolkit import prompt
from prompt_toolkit.completion import FuzzyCompleter, WordCompleter
from utils.conn_psql import execute_query, fetch_results

from owner import Owner
import re

##################################
THIS_SEASON = 2025
SALARY_CAP = 13000
##################################

positions = {
    "C": {"name": "Catcher", "count": 1},
    "1B": {"name": "First Base", "count": 1},
    "2B": {"name": "Second Base", "count": 1},
    "3B": {"name": "Third Base", "count": 1},
    "SS": {"name": "Shortstop", "count": 1},
    "OF": {"name": "Outfield", "count": 3},
    "SP": {"name": "Starting Pitcher", "count": 3},
    "RP": {"name": "Relief Pitcher", "count": 1}
}

###################################
# ✅ OWNER SELECTION

def get_active_owners():
    """Retrieve active owners from the database."""
    query = """
        SELECT id, nickname, first_name, last_name 
        FROM owner 
        WHERE family_status = %s AND active = %s 
        ORDER BY nickname ASC
    """
    return fetch_results(query, (True, True))

def choose_owner():
    """Allow the user to choose an owner using nickname search."""
    owners = get_active_owners()
    
    if not owners:
        print("❌ No active owners found!")
        return None

    owner_dict = {f"{o[1]} ({o[2]} {o[3]}) ({o[0]})": o[0] for o in owners}

    owner_completer = FuzzyCompleter(WordCompleter(list(owner_dict.keys()), ignore_case=True, sentence=True))

    while True:

        selected_owner = prompt("\n🔽 Choose an owner (or type 'x' to exit): ", completer=owner_completer).strip()

        if selected_owner.lower() == "x":
            print("👋 Exiting program...")
            exit()

        match = re.match(r"^(.*?)\s\((.*?)\)\s\((\d+)\)$", selected_owner)

        if match:
            owner_id = int(match.group(3))
            if owner_id in owner_dict.values():
                print(f"✅ Selected: {selected_owner}")
                return owner_id

        elif selected_owner.isdigit() and int(selected_owner) in owner_dict.values():
            print(f"✅ Selected Owner ID: {selected_owner}")
            return int(selected_owner)

        print("❌ Invalid selection. Please try again.")

# ✅ PLAYER SELECTION

def get_available_players(position, selected_players):
    """Retrieve available players for a given position sorted by highest salary."""
    query = f"""
        SELECT id, first_name, last_name, salary 
        FROM player_x_season_detail 
        WHERE season = {THIS_SEASON} 
        AND pos = %s
        AND id NOT IN %s
        ORDER BY salary DESC
    """
    return fetch_results(query, (position, tuple(selected_players) if selected_players else (0,)))

def choose_player(position, roster):
    """Allow user to choose a player from dropdown, filtering out already selected players."""
    players = get_available_players(position, roster["players"])
    
    if not players:
        print(f"❌ No available {positions[position]['name']}s for this position.")
        return None, 0

    # ✅ Create dictionary where key = "First Last (Salary) (ID)"
    player_dict = {f"{p[1]} {p[2]} (${p[3]}) ({p[0]})": (p[0], p[3]) for p in players}

    player_completer = FuzzyCompleter(WordCompleter(list(player_dict.keys()), ignore_case=True, sentence=True))

    while True:

        selected_player = prompt(f"\n🔽 Choose a {positions[position]['name']} (or type 'x' to exit): ", completer=player_completer).strip()

        if selected_player.lower() == "x":
            print("👋 Exiting program...")
            exit()

        match = re.match(r"^(.*?)\s\(\$(\d+)\)\s\((\d+)\)$", selected_player)

        if match:
            player_id = int(match.group(3))
            if player_id in [p[0] for p in player_dict.values()]:
                print(f"✅ Selected {positions[position]['name']}: {selected_player}")
                return player_dict[selected_player]

        elif selected_player.isdigit() and int(selected_player) in [p[0] for p in player_dict.values()]:
            for key, value in player_dict.items():
                if value[0] == int(selected_player):
                    print(f"✅ Selected {positions[position]['name']} ID: {selected_player}")
                    return value

        print(f"❌ Invalid selection. Please choose a {positions[position]['name']} from the list.")

# ✅ INSERT ROSTER
def insert_roster(roster):

    for player_id in roster["players"]:

        query = """
        INSERT INTO owner_x_player (owner_id, player_id, season)
        VALUES (%s, %s, %s)
        """

        values = (roster["owner_id"], player_id, THIS_SEASON)

        execute_query(query, values)

    return True

######################################################
# ✅ MAIN FUNCTION

def main():

    while True:

        print(f"\n*****************************************************")
        print(f"🔹 Add a roster for an owner for the season {THIS_SEASON}.")
        print(f"*******************************************************")

        roster = {"owner_id": None, "players": [], "total_salary": 0}

        # ✅ Let the user choose an owner
        selected_owner_id = choose_owner()

        if selected_owner_id is None:
            print("❌ No valid owner selected. Exiting.")
            return

        roster["owner_id"] = selected_owner_id
        print(f"\n🔹 Owner ID {selected_owner_id} selected. Now choose players.")

        for position, details in positions.items():
            for i in range(details["count"]):  
                while True:  
                    print(f"\n************************************")
                    print(f"Position: {details['name']} ({i+1} of {details['count']})")
                    print(f"************************************")

                    selected_player_id, selected_salary = choose_player(position, roster)

                    if selected_player_id:
                        if roster["total_salary"] + selected_salary > SALARY_CAP:
                            print(f"⚠️ Salary cap exceeded! Current total: {roster['total_salary']}, adding {selected_salary} would exceed {SALARY_CAP}.")
                            print("❌ Choose a different player.")
                            continue  

                        roster["players"].append(selected_player_id)
                        roster["total_salary"] += selected_salary  
                        break

        print("\n✅ Roster successfully created!")
        print(f"🔹 Final Roster: {roster}")
        print(f"💰 Total Salary: {roster['total_salary']} (Max: {SALARY_CAP})")

        salary_init = roster["total_salary"]
        bank_init = SALARY_CAP - salary_init

        # ✅ Insert the roster into the database
        if insert_roster(roster):
            print("✅ Roster inserted into the database successfully.")
        else:
            print("❌ Failed to insert the roster into the database.")

        owner = Owner(roster["owner_id"])

        team_name = owner.get_default_team_name()
        
        query = """
            INSERT into owner_x_season (id, season, salary_init, salary_current, bank_init, bank_current, team_name)
            VALUES (%s, %s, %s, %s, %s, %s, %s)
        """
        values = (roster["owner_id"], THIS_SEASON, salary_init, salary_init, bank_init, bank_init, team_name)
        if execute_query(query, values):
            print("✅ Owner's season details inserted successfully.")
        else:
            print("❌ Failed to insert owner's season details.")
        print("\n🔹 Roster added successfully!")

if __name__ == "__main__":
    main()
