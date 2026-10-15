import AdminBadge from '../../Components/Admin/AdminBadge';

export type FaultOrigin = { device_problem_tag_id: number | null; common_component_id: number | null };

/** A fault's origin is fully derivable from which (if either) catalog it was ticked from — never both, per the 3 fault tables' schema. */
export default function FaultOriginBadge({ device_problem_tag_id: deviceProblemTagId, common_component_id: commonComponentId }: FaultOrigin) {
    if (deviceProblemTagId !== null) {
        return <AdminBadge status="info">Diagnosed</AdminBadge>;
    }

    if (commonComponentId !== null) {
        return <AdminBadge status="neutral">Quick check</AdminBadge>;
    }

    return <AdminBadge status="neutral">Custom</AdminBadge>;
}
