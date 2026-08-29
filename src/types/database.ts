// ============================================================================
// Placeholder Supabase database types.
//
// Once you have a live Supabase project with the migrations in
// `supabase/migrations/` applied, generate the real types with:
//
//   npx supabase login
//   npx supabase link --project-ref <your-project-ref>
//   npx supabase gen types typescript --linked > src/types/database.ts
//
// (or, for a local `supabase start` stack: `--local` instead of `--linked`)
//
// Until then this loose placeholder keeps `createClient<Database>()` typed
// without blocking development — every table/view/function accepts `any`.
// ============================================================================

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type Database = any
