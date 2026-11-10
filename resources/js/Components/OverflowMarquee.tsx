import { useEffect, useRef, useState } from 'react';

interface OverflowMarqueeProps {
    text: string;
    className?: string;
}

/** Moves overflowing text continuously while leaving text that already fits completely still. */
export default function OverflowMarquee({ text, className = '' }: OverflowMarqueeProps) {
    const containerRef = useRef<HTMLSpanElement>(null);
    const textRef = useRef<HTMLSpanElement>(null);
    const [isOverflowing, setIsOverflowing] = useState(false);

    useEffect(() => {
        const container = containerRef.current;
        const content = textRef.current;

        if (!container || !content) return;

        const updateOverflow = () => setIsOverflowing(content.scrollWidth > container.clientWidth + 1);
        const observer = new ResizeObserver(updateOverflow);

        updateOverflow();
        observer.observe(container);
        observer.observe(content);

        return () => observer.disconnect();
    }, [text]);

    return (
        <span ref={containerRef} className={`block min-w-0 overflow-hidden ${className}`} title={text}>
            {isOverflowing ? (
                <span className="overflow-marquee-track motion-reduce:animate-none">
                    <span ref={textRef} className="shrink-0">{text}</span>
                    <span aria-hidden="true" className="shrink-0">{text}</span>
                </span>
            ) : (
                <span ref={textRef} className="block whitespace-nowrap">{text}</span>
            )}
        </span>
    );
}
