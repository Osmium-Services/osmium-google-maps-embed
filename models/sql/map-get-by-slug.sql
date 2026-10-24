SELECT id, name, slug, embed_url FROM {TABLE} WHERE slug = :slug AND deleted_at IS NULL
