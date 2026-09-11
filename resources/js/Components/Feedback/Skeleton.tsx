interface SkeletonProps {
    /** Caller controls dimensions/shape (e.g. "h-4 w-32", "h-10 w-10 rounded-full") — a skeleton should match the real content's footprint (UI/UX §13.1), never one generic block. */
    className?: string;
}

export default function Skeleton({ className = '' }: SkeletonProps) {
    return (
        <div
            className={`skeleton-shimmer rounded-admin-input ${className}`}
            role="presentation"
            aria-hidden="true"
        />
    );
}
