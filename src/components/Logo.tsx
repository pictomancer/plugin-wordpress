// Pictomancer mark: purple disc with an offset white highlight, mirrored from
// the web dashboard nav so the plugin reads as the same product.
export default function Logo() {
  return (
    <svg
      width="26"
      height="26"
      viewBox="0 0 512 512"
      fill="none"
      xmlns="http://www.w3.org/2000/svg"
      aria-hidden="true"
    >
      <defs>
        <clipPath id="pic-logo-clip">
          <circle cx="256" cy="256" r="220" />
        </clipPath>
      </defs>
      <circle cx="256" cy="256" r="220" fill="#a855f7" />
      <circle
        cx="172"
        cy="155"
        r="190"
        fill="white"
        opacity="0.35"
        clipPath="url(#pic-logo-clip)"
      />
    </svg>
  );
}
