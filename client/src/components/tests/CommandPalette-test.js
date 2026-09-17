import React from 'react';
import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom';
import CommandPalette from '../CommandPalette';

const ENDPOINTS = { commands: 'admin/cmdk/commands', search: 'admin/cmdk/search' };

const COMMANDS_RESPONSE = {
  groups: [
    {
      code: 'content',
      title: 'Content',
      commands: [
        {
          code: 'new-page',
          label: 'Create a new page',
          icon: '',
          keywords: ['add'],
          link: 'admin/pages/add',
          shortcut: '',
        },
      ],
    },
  ],
};

function mockFetchOnce(data) {
  global.fetch.mockResolvedValueOnce({
    ok: true,
    json: () => Promise.resolve(data),
  });
}

describe('CommandPalette', () => {
  let originalLocation;

  // jsdom doesn't implement ResizeObserver, which cmdk uses internally to track list height.
  beforeAll(() => {
    global.ResizeObserver = class ResizeObserver {
      observe() {}

      unobserve() {}

      disconnect() {}
    };
  });

  beforeEach(() => {
    global.fetch = jest.fn();
    originalLocation = window.location;
    delete window.location;
    window.location = { href: '' };
  });

  afterEach(() => {
    window.location = originalLocation;
    jest.restoreAllMocks();
  });

  test('is not rendered until opened', () => {
    render(<CommandPalette endpoints={ENDPOINTS} />);

    expect(screen.queryByPlaceholderText(/search for pages/i)).not.toBeInTheDocument();
    expect(global.fetch).not.toHaveBeenCalled();
  });

  test('opens with Cmd+K and lazily loads commands', async () => {
    mockFetchOnce(COMMANDS_RESPONSE);
    render(<CommandPalette endpoints={ENDPOINTS} />);

    await userEvent.keyboard('{Meta>}k{/Meta}');

    expect(await screen.findByPlaceholderText(/search for pages/i)).toBeInTheDocument();
    expect(await screen.findByText('Create a new page')).toBeInTheDocument();
    expect(global.fetch).toHaveBeenCalledWith(ENDPOINTS.commands, expect.any(Object));
  });

  test('closes on Escape', async () => {
    mockFetchOnce(COMMANDS_RESPONSE);
    render(<CommandPalette endpoints={ENDPOINTS} />);

    await userEvent.keyboard('{Meta>}k{/Meta}');
    expect(await screen.findByPlaceholderText(/search for pages/i)).toBeInTheDocument();

    await userEvent.keyboard('{Escape}');

    await waitFor(() => {
      expect(screen.queryByPlaceholderText(/search for pages/i)).not.toBeInTheDocument();
    });
  });

  test('opens when the CMSMenu trigger link is clicked', async () => {
    mockFetchOnce(COMMANDS_RESPONSE);
    render(<CommandPalette endpoints={ENDPOINTS} />);

    const trigger = document.createElement('a');
    trigger.setAttribute('data-cmdk-trigger', 'true');
    document.body.appendChild(trigger);

    await userEvent.click(trigger);

    expect(await screen.findByPlaceholderText(/search for pages/i)).toBeInTheDocument();

    document.body.removeChild(trigger);
  });

  test('selecting a command navigates to its link and closes the palette', async () => {
    mockFetchOnce(COMMANDS_RESPONSE);
    render(<CommandPalette endpoints={ENDPOINTS} />);

    await userEvent.keyboard('{Meta>}k{/Meta}');
    const item = await screen.findByText('Create a new page');

    await userEvent.click(item);

    expect(window.location.href).toBe('admin/pages/add');
    await waitFor(() => {
      expect(screen.queryByPlaceholderText(/search for pages/i)).not.toBeInTheDocument();
    });
  });

  test('typing a query fetches and renders live search results alongside static commands', async () => {
    mockFetchOnce(COMMANDS_RESPONSE);
    render(<CommandPalette endpoints={ENDPOINTS} />);

    await userEvent.keyboard('{Meta>}k{/Meta}');
    await screen.findByPlaceholderText(/search for pages/i);

    mockFetchOnce({
      results: [
        {
          title: 'About Us',
          link: 'admin/pages/edit/show/2',
          description: 'Home',
          icon: '',
          group: 'Pages',
        },
      ],
    });

    await userEvent.type(screen.getByPlaceholderText(/search for pages/i), 'about');

    expect(await screen.findByText('About Us')).toBeInTheDocument();
    expect(global.fetch).toHaveBeenCalledWith(
      `${ENDPOINTS.search}?q=about`,
      expect.any(Object)
    );
  });

  test('does not search for queries shorter than the minimum length', async () => {
    mockFetchOnce(COMMANDS_RESPONSE);
    render(<CommandPalette endpoints={ENDPOINTS} />);

    await userEvent.keyboard('{Meta>}k{/Meta}');
    await screen.findByPlaceholderText(/search for pages/i);

    global.fetch.mockClear();
    await userEvent.type(screen.getByPlaceholderText(/search for pages/i), 'a');

    await new Promise((resolve) => { setTimeout(resolve, 250); });
    expect(global.fetch).not.toHaveBeenCalled();
  });
});
