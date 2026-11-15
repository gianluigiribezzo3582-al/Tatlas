/** Extracts the numeric id from a public studio path segment like "12-inchiostro-nero". */
export function studioIdFromRef(ref: string | undefined | null): number | null {
  const match = /^(\d+)(?:-[a-z0-9-]*)?$/.exec(ref ?? '');
  return match ? Number(match[1]) : null;
}
