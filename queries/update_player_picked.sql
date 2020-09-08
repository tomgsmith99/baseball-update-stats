UPDATE players_current pc
INNER JOIN (
  SELECT player_id, SUM({{column}}) as total
  FROM ownersXrosters_current
  WHERE season={{season}}
  GROUP BY player_id
) x ON pc.player_id = x.player_id
SET pc.{{column}} = x.total