import React, { useCallback, useEffect, useRef, useState } from 'react';
import { Command } from 'cmdk';
import filterCommands from '../lib/filterCommands';
import groupSearchResults from '../lib/groupSearchResults';

const SEARCH_DEBOUNCE_MS = 200;
const MIN_QUERY_LENGTH = 2;

function fetchJson(url) {
  return fetch(url, {
    credentials: 'same-origin',
    headers: { 'X-Requested-With': 'XMLHttpRequest' },
  }).then((response) => (response.ok ? response.json() : null));
}

/**
 * The command palette overlay. Mounted once into its own React root (see bundles/bundle.js),
 * independent of the CMS's own Redux/Injector-driven React tree.
 */
const CommandPalette = ({ endpoints }) => {
  const [open, setOpen] = useState(false);
  const [query, setQuery] = useState('');
  const [groups, setGroups] = useState([]);
  const [commandsLoaded, setCommandsLoaded] = useState(false);
  const [searchResults, setSearchResults] = useState([]);
  const debounceRef = useRef(null);

  const loadCommands = useCallback(() => {
    if (commandsLoaded) {
      return;
    }

    fetchJson(endpoints.commands)
      .then((data) => setGroups((data && data.groups) || []))
      .catch(() => setGroups([]))
      .finally(() => setCommandsLoaded(true));
  }, [endpoints, commandsLoaded]);

  // Global open triggers: Cmd/Ctrl+K anywhere in the CMS, or a click on the CMSMenu trigger link
  // added by LeftAndMainCmdkExtension (identified by a stable data attribute, not a class name,
  // so it keeps working even if the menu item's styling classes change).
  useEffect(() => {
    const handleKeyDown = (event) => {
      if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        setOpen((current) => !current);
      }
    };

    const handleClick = (event) => {
      if (event.target.closest('[data-cmdk-trigger]')) {
        // The CMSMenu link's href is "#", which the admin SPA's own router also tries to
        // handle. preventDefault() alone only stops the browser's native anchor navigation -
        // capturing the event and stopping propagation keeps the CMS router from also acting on
        // it (which, combined with the CMS's <base> tag, would otherwise navigate to the site's
        // front end root instead of just staying put).
        event.preventDefault();
        event.stopPropagation();
        setOpen(true);
      }
    };

    document.addEventListener('keydown', handleKeyDown);
    document.addEventListener('click', handleClick, true);

    return () => {
      document.removeEventListener('keydown', handleKeyDown);
      document.removeEventListener('click', handleClick, true);
    };
  }, []);

  useEffect(() => {
    if (open) {
      loadCommands();
    } else {
      setQuery('');
      setSearchResults([]);
    }
  }, [open, loadCommands]);

  useEffect(() => {
    clearTimeout(debounceRef.current);

    if (query.trim().length < MIN_QUERY_LENGTH) {
      setSearchResults([]);
      return undefined;
    }

    debounceRef.current = setTimeout(() => {
      fetchJson(`${endpoints.search}?q=${encodeURIComponent(query.trim())}`)
        .then((data) => setSearchResults((data && data.results) || []))
        .catch(() => setSearchResults([]));
    }, SEARCH_DEBOUNCE_MS);

    return () => clearTimeout(debounceRef.current);
  }, [query, endpoints]);

  const handleSelect = (link) => {
    setOpen(false);

    if (link) {
      window.location.href = link;
    }
  };

  const filteredGroups = groups
    .map((group) => ({ ...group, commands: filterCommands(group.commands, query) }))
    .filter((group) => group.commands.length > 0);

  const resultGroups = groupSearchResults(searchResults);

  return (
    <Command.Dialog
      open={open}
      onOpenChange={setOpen}
      shouldFilter={false}
      label="Command palette"
      overlayClassName="wilr-cmdk-overlay"
      contentClassName="wilr-cmdk-content"
    >
      <Command.Input
        value={query}
        onValueChange={setQuery}
        placeholder="Search for pages, files, or actions…"
        autoFocus
      />
      <Command.List>
        <Command.Empty>No results found.</Command.Empty>
        {filteredGroups.map((group) => (
          <Command.Group key={group.code} heading={group.title}>
            {group.commands.map((command) => (
              <Command.Item
                key={command.code}
                value={`${group.code}-${command.code}`}
                keywords={[command.label, ...(command.keywords || [])]}
                onSelect={() => handleSelect(command.link)}
              >
                {command.icon && (
                  <span className={`wilr-cmdk-icon ${command.icon}`} aria-hidden="true" />
                )}
                <span className="wilr-cmdk-item__label">{command.label}</span>
                {command.shortcut && (
                  <span className="wilr-cmdk-item__shortcut">{command.shortcut}</span>
                )}
              </Command.Item>
            ))}
          </Command.Group>
        ))}
        {resultGroups.map(([groupName, results]) => (
          <Command.Group key={groupName} heading={groupName}>
            {results.map((result, index) => (
              <Command.Item
                // eslint-disable-next-line react/no-array-index-key
                key={`${groupName}-${index}`}
                value={`search-${groupName}-${result.title}-${index}`}
                onSelect={() => handleSelect(result.link)}
              >
                {result.icon && (
                  <span className={`wilr-cmdk-icon ${result.icon}`} aria-hidden="true" />
                )}
                <span className="wilr-cmdk-item__label">{result.title}</span>
                {result.description && (
                  <span className="wilr-cmdk-item__description">{result.description}</span>
                )}
              </Command.Item>
            ))}
          </Command.Group>
        ))}
      </Command.List>
      <div className="wilr-cmdk-footer">
        <span><kbd>&uarr;</kbd><kbd>&darr;</kbd> to navigate</span>
        <span><kbd>&crarr;</kbd> to select</span>
        <span><kbd>esc</kbd> to close</span>
      </div>
    </Command.Dialog>
  );
};

export default CommandPalette;
