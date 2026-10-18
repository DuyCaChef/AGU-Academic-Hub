import { Link } from "@inertiajs/react";

export default function AppLayout({ children }) {
    return (
        <div className="min-h-screen bg-surface-page">
            <header className="border-b border-border-default bg-surface-default">
                <nav className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                    <Link
                        href={route("home")}
                        className="font-semibold text-brand-primary"
                    >
                        AGU Academic Hub
                    </Link>
                    <span className="text-sm text-text-secondary">
                        Knowledge & Community
                    </span>
                </nav>
            </header>
            <main>{children}</main>
        </div>
    );
}
