import {
  render,
} from '@testing-library/react';

import {
  MemoryRouter,
  Route,
  Routes,
} from 'react-router-dom';

import TestResultForm from '../TestResultForm';

const routes = (
  <Routes>
    <Route
      path="/test-results/new"
      element={<TestResultForm />}
    />
    <Route
      path="/test-results/:id/edit"
      element={<TestResultForm />}
    />
    <Route
      path="/test-results/:id"
      element={
        <div>Result detail route</div>
      }
    />
    <Route
      path="/test-results"
      element={
        <div>Result list route</div>
      }
    />
  </Routes>
);

const renderForm = (
  entry: string
) => render(
  <MemoryRouter
    initialEntries={[entry]}
  >
    {routes}
  </MemoryRouter>
);

export const renderCreateForm = (
  entry: string
) => renderForm(entry);

export const renderEditForm = (
  resultUuid: string
) => renderForm(
  `/test-results/${resultUuid}/edit`
);
