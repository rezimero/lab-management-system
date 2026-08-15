import clinicianPortalClient from
  '../api/clinicianPortalClient';

import PortalNotificationsPage from
  '../../notifications/components/PortalNotificationsPage';

const ClinicianPortalNotifications = () => (
  <PortalNotificationsPage
    client={clinicianPortalClient}
    prefix="/portal/clinician"
    title="Notifications"
  />
);

export default ClinicianPortalNotifications;
