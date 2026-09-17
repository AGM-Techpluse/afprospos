import Icon from '../../Components/Icons/Icon';

interface EligibilityChecklistProps {
    coveredScope: string[];
    exclusions: string[];
}

/** WAR-BR-05 — surfaces a policy's covered scope and exclusions as a reference checklist for the staff member assessing a claim; this is read-only context, not a form (the assess decision itself is a plain eligible/not-eligible choice on the Show page). */
export default function EligibilityChecklist({ coveredScope, exclusions }: EligibilityChecklistProps) {
    return (
        <div className="bg-admin-surface border border-admin-border rounded-admin-card p-4">
            <div className="text-sm font-semibold text-admin-text mb-3">Policy coverage reference</div>

            {coveredScope.length > 0 && (
                <div className="mb-3">
                    <div className="text-xs font-semibold text-admin-text2 uppercase tracking-wide mb-1.5">Covered</div>
                    <ul className="flex flex-col gap-1">
                        {coveredScope.map((item) => (
                            <li key={item} className="flex items-center gap-2 text-sm text-admin-text">
                                <Icon name="check-circle" className="text-admin-green shrink-0" />
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {exclusions.length > 0 && (
                <div>
                    <div className="text-xs font-semibold text-admin-text2 uppercase tracking-wide mb-1.5">Excluded</div>
                    <ul className="flex flex-col gap-1">
                        {exclusions.map((item) => (
                            <li key={item} className="flex items-center gap-2 text-sm text-admin-text">
                                <Icon name="x-circle" className="text-admin-red shrink-0" />
                                {item}
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
}
