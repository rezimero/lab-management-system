import patientPortalClient from
  '../api/patientPortalClient';

import PortalNotificationsPage from
  '../../notifications/components/PortalNotificationsPage';

const PatientPortalNotifications = () => (
  <PortalNotificationsPage
    client={patientPortalClient}
    prefix="/portal/patient"
    title="Notifications"
  />
);

export default PatientPortalNotifications;
