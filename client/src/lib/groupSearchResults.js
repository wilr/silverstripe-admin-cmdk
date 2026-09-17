/**
 * Groups a flat list of search results (each tagged with a `group` name by the
 * `CommandSearchProvider`) into `[groupName, results[]]` pairs, preserving the order
 * groups first appear in.
 *
 * @param {Array<{group?: string}>} results
 * @returns {Array<[string, Array]>}
 */
export default function groupSearchResults(results) {
  const groups = new Map();

  results.forEach((result) => {
    const key = result.group || 'Search results';

    if (!groups.has(key)) {
      groups.set(key, []);
    }

    groups.get(key).push(result);
  });

  return Array.from(groups.entries());
}
