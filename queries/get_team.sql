
SELECT o.acquired, o.player_id, o.points, o.benched,
	p.pos, p.salary, p.player_id, p.team,
	p.picked, p.yesterday, p.recent, p.value,
	P.fnf
	FROM {{owner_table}} AS o, {{player_table}} AS p, players AS P
	WHERE o.season={{season}}
	AND p.season={{season}}
	AND owner_id={{owner_id}}
	AND o.player_id=p.player_id
	AND P.Player_ID=p.player_id
	ORDER BY p.salary DESC
