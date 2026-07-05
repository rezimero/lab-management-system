import React, { useEffect, useState } from 'react';
import api from '../api/client';

interface User {
  id: number;
  name: string;
  email: string;
  role: string;
}

interface Tenant {
  id: number;
  name: string;
  domain: string;
}

const TenantDashboard: React.FC = () => {
  const [user, setUser] = useState<User | null>(null);
  const [tenant, setTenant] = useState<Tenant | null>(null);
  const [users, setUsers] = useState<User[]>([]);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const userRes = await api.get('/user');
        setUser(userRes.data.user);
        setTenant(userRes.data.tenant);

        const usersRes = await api.get('/users');
        setUsers(usersRes.data.users.data);
      } catch (error) {
        console.error('Error fetching data:', error);
      }
    };

    fetchData();
  }, []);

  return (
    <div>
      <h2>Dashboard</h2>
      <p>Welcome, {user?.name}</p>
      <p>Lab: {tenant?.name}</p>
      <p>Role: {user?.role}</p>
      
      <h3>Users in {tenant?.name}</h3>
      <ul>
        {users.map((u) => (
          <li key={u.id}>{u.name} ({u.email}) - {u.role}</li>
        ))}
      </ul>

      <button onClick={() => {
        localStorage.clear();
        window.location.href = '/login';
      }}>Logout</button>
    </div>
  );
};

export default TenantDashboard;