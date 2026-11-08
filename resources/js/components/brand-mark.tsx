import type { SVGAttributes } from 'react';

/**
 * The DealFlow mark: a D whose counter holds a forward chevron.
 * Drawn with currentColor so it works on light, dark, and tinted panels.
 */
export default function BrandMark(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 128 128"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
            focusable="false"
        >
            <path
                d="M42 30 H60 C83 30 96 44 96 64 C96 84 83 98 60 98 H42 Z"
                fill="none"
                stroke="currentColor"
                strokeWidth="12"
                strokeLinejoin="round"
            />
            <path
                d="M58 49 L77 64 L58 79"
                fill="none"
                stroke="#10B981"
                strokeWidth="10"
                strokeLinecap="round"
                strokeLinejoin="round"
            />
        </svg>
    );
}
