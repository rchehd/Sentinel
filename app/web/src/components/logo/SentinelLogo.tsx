interface SentinelLogoProps {
  size?: number
}

export function SentinelLogo({ size = 48 }: SentinelLogoProps) {
  const innerSize = size * 0.5
  const containerSize = size * 0.75

  return (
    <div
      className="flex items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800 mb-4 mx-auto"
      style={{ width: containerSize, height: containerSize }}
    >
      <svg
        fill="none"
        width={innerSize}
        height={innerSize}
        viewBox="0 0 100 100"
        xmlns="http://www.w3.org/2000/svg"
        className="text-slate-900 dark:text-white"
      >
        <path
          clipRule="evenodd"
          d="M20 20H80V35H35V50H80V80H20V65H65V50H20V20ZM80 20V35V50V80V65V50V20Z"
          fill="currentColor"
          fillRule="evenodd"
        />
      </svg>
    </div>
  )
}
