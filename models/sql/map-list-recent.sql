SELECT m.id, m.name, m.slug, m.embed_url, m.created_at, m.updated_at, m.deleted_at, CL.user_name AS changed_by, CU.avatar AS changed_by_avatar
FROM {TABLE} m
LEFT JOIN {PREFIX}changelog AS CL ON CL.id = (SELECT MAX(id) FROM {PREFIX}changelog WHERE record_type = 'google_maps_embed' AND record_id = m.id)
LEFT JOIN {PREFIX}users AS CU ON CU.id = CL.user_id
WHERE (m.created_at >= NOW() - INTERVAL 24 HOUR OR m.updated_at >= NOW() - INTERVAL 24 HOUR OR m.deleted_at >= NOW() - INTERVAL 24 HOUR)
ORDER BY GREATEST(COALESCE(m.updated_at, m.created_at), m.created_at, COALESCE(m.deleted_at, m.created_at)) DESC, m.id DESC
LIMIT 10
