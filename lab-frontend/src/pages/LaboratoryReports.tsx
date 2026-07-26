import {
  useNavigate,
} from 'react-router-dom';

import Layout from
  '../components/Layout/Layout';

import {
  useAuth,
} from '../contexts/AuthContext';

import GenerateReportDialog from
  '../features/laboratory-reports/components/GenerateReportDialog';

import ReportListHeader from
  '../features/laboratory-reports/components/ReportListHeader';

import ReportListTable from
  '../features/laboratory-reports/components/ReportListTable';

import ReportPagination from
  '../features/laboratory-reports/components/ReportPagination';

import ReportSearchBar from
  '../features/laboratory-reports/components/ReportSearchBar';

import {
  useGenerateLaboratoryReport,
} from '../features/laboratory-reports/hooks/useGenerateLaboratoryReport';

import {
  useLaboratoryReports,
} from '../features/laboratory-reports/hooks/useLaboratoryReports';

import {
  roleAllowsReport,
} from '../features/laboratory-reports/permissions/reportPermissions';

const LaboratoryReports = () => {
  const navigate = useNavigate();
  const { user } = useAuth();

  const reports =
    useLaboratoryReports();

  const generation =
    useGenerateLaboratoryReport(
      (report) => {
        navigate(
          `/laboratory-reports/${report.uuid}`
        );
      }
    );

  const canGenerate =
    roleAllowsReport(
      user?.role,
      'generate'
    );

  return (
    <Layout title="Laboratory reports">
      <ReportListHeader
        canGenerate={canGenerate}
        onGenerate={
          generation.openDialog
        }
      />

      <ReportSearchBar
        query={reports.query}
        loading={reports.loading}
        onQueryChange={
          reports.setQuery
        }
        onSearch={
          reports.applySearch
        }
      />

      <ReportListTable
        reports={reports.reports}
        loading={reports.loading}
        error={reports.error}
        onOpen={(uuid) => {
          navigate(
            `/laboratory-reports/${uuid}`
          );
        }}
      />

      <ReportPagination
        meta={reports.meta}
        page={reports.page}
        onPageChange={
          reports.setPage
        }
      />

      <GenerateReportDialog
        open={generation.open}
        orderUuid={
          generation.orderUuid
        }
        submitting={
          generation.submitting
        }
        error={generation.error}
        onOrderUuidChange={
          generation.setOrderUuid
        }
        onClose={
          generation.closeDialog
        }
        onGenerate={() => {
          void generation.generate();
        }}
      />
    </Layout>
  );
};

export default LaboratoryReports;
