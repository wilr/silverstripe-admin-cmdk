/**
 * Local, dependency-free filtering for the statically-registered commands fetched from
 * `admin/cmdk/commands`. Deliberately not using cmdk's own built-in `command-score` filter here -
 * this needs to coexist with the separately-filtered, server-side "search results" group, and
 * mixing two different scoring systems for what's ultimately one visible list is more confusing
 * than a plain substring match.
 *
 * @param {Array<{label: string, keywords?: string[]}>} commands
 * @param {string} query
 * @returns {Array}
 */
export default function filterCommands(commands, query) {
  const needle = query.trim().toLowerCase();

  if (!needle) {
    return commands;
  }

  return commands.filter((command) => {
    const haystack = [command.label, ...(command.keywords || [])]
      .join(' ')
      .toLowerCase();

    return haystack.includes(needle);
  });
}
