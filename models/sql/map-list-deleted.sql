SELECT m.id, m.name, m.slug, m.embed_url, m.deleted_at, CL.user_name AS changed_by, CU.avatar AS changed_by_avatar
FROM {TABLE} m
LEFT JOIN {PREFIX}changelog AS CL ON CL.id = (SELECT MAX(id) FROM {PREFIX}changelog WHERE record_type = 'google_maps_embed' AND record_id = m.id)
LEFT JOIN {PREFIX}users AS CU ON CU.id = CL.user_id
WHERE m.deleted_at IS NOT NULL
ORDER BY m.deleted_at DESC
