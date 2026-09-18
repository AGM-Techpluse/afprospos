interface CustomerAvatarProps {
    name: string;
    avatarUrl?: string | null;
    size?: number;
    className?: string;
}

/** Shows the customer's uploaded photo when set, else the initial-letter circle used everywhere today — one place to change when a photo becomes available instead of every call site branching on avatarUrl itself. */
export default function CustomerAvatar({ name, avatarUrl, size = 40, className = '' }: CustomerAvatarProps) {
    const style = { width: size, height: size, fontSize: size * 0.4 };

    if (avatarUrl) {
        return (
            <img
                src={avatarUrl}
                alt={name}
                style={style}
                className={`rounded-customer-pill object-cover shrink-0 ${className}`}
            />
        );
    }

    return (
        <div
            style={style}
            className={`rounded-customer-pill bg-customer-blue text-white font-bold flex items-center justify-center shrink-0 ${className}`}
        >
            {name.charAt(0).toUpperCase()}
        </div>
    );
}
