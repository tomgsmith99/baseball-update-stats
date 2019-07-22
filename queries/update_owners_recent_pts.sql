UPDATE owners_current oc
INNER JOIN (
  SELECT owner_id, points
  FROM ownerXpoints
  WHERE season={{season}}
  AND day={{day}}
) x ON oc.owner_id = x.owner_id
SET oc.{{column}} = oc.points - x.points