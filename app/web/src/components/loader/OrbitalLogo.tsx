import styles from './OrbitalLogo.module.scss'

interface OrbitalLogoProps {
  size?: number
}

export function OrbitalLogo({ size = 80 }: OrbitalLogoProps) {
  const innerSize = size * 0.45

  return (
    <div className={styles.container} style={{ width: size, height: size }}>
      {/* S Logo */}
      <svg
        className={styles.logo}
        width={innerSize}
        height={innerSize}
        viewBox="0 0 100 100"
        fill="none"
        xmlns="http://www.w3.org/2000/svg"
      >
        <path
          clipRule="evenodd"
          d="M20 20H80V35H35V50H80V80H20V65H65V50H20V20ZM80 20V35V50V80V65V50V20Z"
          fill="currentColor"
          fillOpacity="0.95"
          fillRule="evenodd"
        />
      </svg>

      {/* Orbit ring 1 */}
      <div className={styles.ring1} />

      {/* Orbit ring 2 */}
      <div className={styles.ring2} />
    </div>
  )
}
