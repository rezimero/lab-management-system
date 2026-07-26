import {
  render,
} from '@testing-library/react';

import {
  MemoryRouter,
  Route,
  Routes,
} from 'react-router-dom';

import TestResultView from '../TestResultView';

import {
  resultUuid,
} from './testResultViewFixtures';

export const renderResultView = () =>
  render(
    <MemoryRouter
      initialEntries={[
        `/test-results/${resultUuid}`,
      ]}
    >
      <Routes>
        <Route
          path="/test-results/:id"
          element={<TestResultView />}
        />
        <Route
          path="/test-results/:id/edit"
          element={
            <div>Result edit route</div>
          }
        />
        <Route
          path="/test-results"
          element={
            <div>Result list route</div>
          }
        />
      </Routes>
    </MemoryRouter>
  );
