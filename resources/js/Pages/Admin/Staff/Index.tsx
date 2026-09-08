import { useForm } from '@inertiajs/react';

type StaffRow = {
    id: number;
    name: string;
    email: string;
    phone: string;
    status: string;
    roles: string[];
};

export default function StaffIndex({ staff }: { staff: StaffRow[] }) {
    const { post } = useForm();

    function deactivate(id: number) {
        post(`/admin/staff/${id}/deactivate`);
    }

    return (
        <div>
            <h1>Staff</h1>
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Roles</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    {staff.map((member) => (
                        <tr key={member.id}>
                            <td>{member.name}</td>
                            <td>{member.email}</td>
                            <td>{member.status}</td>
                            <td>{member.roles.join(', ')}</td>
                            <td>
                                {member.status === 'active' && (
                                    <button onClick={() => deactivate(member.id)}>
                                        Deactivate
                                    </button>
                                )}
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
