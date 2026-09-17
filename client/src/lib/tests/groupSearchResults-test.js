import groupSearchResults from '../groupSearchResults';

describe('groupSearchResults', () => {
  test('groups results by their group name, preserving first-seen order', () => {
    const results = [
      { title: 'Home', group: 'Pages' },
      { title: 'logo.png', group: 'Files' },
      { title: 'About', group: 'Pages' },
    ];

    expect(groupSearchResults(results)).toEqual([
      ['Pages', [results[0], results[2]]],
      ['Files', [results[1]]],
    ]);
  });

  test('falls back to a default group for results with no group', () => {
    const results = [{ title: 'Untitled' }];

    expect(groupSearchResults(results)).toEqual([
      ['Search results', results],
    ]);
  });

  test('returns an empty array for no results', () => {
    expect(groupSearchResults([])).toEqual([]);
  });
});
