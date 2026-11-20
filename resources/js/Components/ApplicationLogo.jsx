export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <img
            src="/storage/imgs/Logo-agu.webp"
            alt="Logo Trường Đại học An Giang"
            className={`object-contain ${className}`}
            {...props}
        />
    );
}
