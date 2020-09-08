UPDATE ownersXseasons_current oc
INNER JOIN (
  SELECT owner_id, SUM(points) as total_points
  FROM ownersXrosters_current
  WHERE season={{season}}
  GROUP BY owner_id
) x ON oc.owner_id = x.owner_id
SET oc.points = x.total_points