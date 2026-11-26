export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <img
            src="/images/Logo-DH-An-Giang-AGU.webp"
            alt="Logo Trường Đại học An Giang"
            className={`object-contain ${className}`}
            {...props}
        />
    );
}
