import filterCommands from '../filterCommands';

describe('filterCommands', () => {
  const commands = [
    { code: 'new-page', label: 'Create a new page', keywords: ['add', 'page'] },
    { code: 'open-security', label: 'Open Security', keywords: ['users', 'permissions'] },
  ];

  test('returns all commands when the query is empty', () => {
    expect(filterCommands(commands, '')).toEqual(commands);
    expect(filterCommands(commands, '   ')).toEqual(commands);
  });

  test('matches on label, case-insensitively', () => {
    expect(filterCommands(commands, 'security')).toEqual([commands[1]]);
    expect(filterCommands(commands, 'SECURITY')).toEqual([commands[1]]);
  });

  test('matches on keywords', () => {
    expect(filterCommands(commands, 'permissions')).toEqual([commands[1]]);
  });

  test('returns an empty array when nothing matches', () => {
    expect(filterCommands(commands, 'nonexistent')).toEqual([]);
  });

  test('treats a command with no keywords as safe to filter', () => {
    const withoutKeywords = [{ code: 'a', label: 'Alpha' }];
    expect(filterCommands(withoutKeywords, 'alpha')).toEqual(withoutKeywords);
  });
});
