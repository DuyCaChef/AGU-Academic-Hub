import React from "react";
import { Link } from "@inertiajs/react";
import ApplicationLogo from "@/Components/ApplicationLogo";
import {
    GraduationCap,
    BookOpen,
    Mail,
    Phone,
    MapPin,
    ShieldCheck,
    FileText,
    ExternalLink,
} from "lucide-react";

export default function StudentFooter() {
    return (
        <footer className="border-t border-slate-200 bg-white text-slate-600 mt-auto">
            <div className="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
                <div className="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                    {/* Cột 1: Thông tin thương hiệu AGU */}
                    <div className="space-y-4">
                        <div className="flex items-center gap-3">
                            <ApplicationLogo
                                className="h-10 w-10"
                                alt="Logo AGU"
                                onError={(e) => {
                                    e.currentTarget.style.display = "none";
                                }}
                            />
                            <div>
                                <span className="text-base font-semibold text-slate-900 tracking-tight block">
                                    AGU Academic Hub
                                </span>
                                <span className="text-xs text-slate-500 block">
                                    ĐHQG-HCM • Trường Đại học An Giang
                                </span>
                            </div>
                        </div>
                        <p className="text-xs leading-relaxed text-slate-500">
                            Hệ thống lưu trữ, chia sẻ tài liệu học tập và tra cứu đề cương môn học chính thức dành cho cộng đồng sinh viên, giảng viên và cựu sinh viên AGU.
                        </p>
                    </div>

                    {/* Cột 2: Danh mục học thuật */}
                    <div>
                        <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-900 mb-3">
                            Danh mục học thuật
                        </h3>
                        <ul className="space-y-2 text-xs">
                            <li>
                                <Link
                                    href={route("courses.index")}
                                    className="hover:text-[#005baa] transition-colors"
                                >
                                    Kho môn học & đề cương
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={route("faculties.index")}
                                    className="hover:text-[#005baa] transition-colors"
                                >
                                    Danh sách khoa & bộ môn
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={route("documents.index")}
                                    className="hover:text-[#005baa] transition-colors"
                                >
                                    Kho tài liệu đã duyệt
                                </Link>
                            </li>
                            <li>
                                <Link
                                    href={route("document-requests.index")}
                                    className="hover:text-[#005baa] transition-colors"
                                >
                                    Săn thưởng tài liệu (Bounty)
                                </Link>
                            </li>
                        </ul>
                    </div>

                    {/* Cột 3: Quy chế & Điều khoản */}
                    <div>
                        <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-900 mb-3">
                            Chính sách & Hướng dẫn
                        </h3>
                        <ul className="space-y-2 text-xs">
                            <li>
                                <Link
                                    href={route("documents.create")}
                                    className="hover:text-[#005baa] transition-colors"
                                >
                                    Quy định đăng tải tài liệu
                                </Link>
                            </li>
                            <li>
                                <span className="text-slate-400 cursor-not-allowed">
                                    Tiêu chuẩn kiểm duyệt nội dung
                                </span>
                            </li>
                            <li>
                                <span className="text-slate-400 cursor-not-allowed">
                                    Chính sách điểm uy tín AGU Trust
                                </span>
                            </li>
                            <li>
                                <span className="text-slate-400 cursor-not-allowed">
                                    Bảo vệ quyền sở hữu trí tuệ
                                </span>
                            </li>
                        </ul>
                    </div>

                    {/* Cột 4: Thông tin liên hệ cơ sở */}
                    <div>
                        <h3 className="text-xs font-semibold uppercase tracking-wider text-slate-900 mb-3">
                            Thông tin liên hệ
                        </h3>
                        <ul className="space-y-2.5 text-xs text-slate-500">
                            <li className="flex items-start gap-2">
                                <MapPin size={15} className="mt-0.5 shrink-0 text-slate-400" />
                                <span>Số 18 Ung Văn Khiêm, P. Đông Xuyên, TP. Long Xuyên, An Giang</span>
                            </li>
                            <li className="flex items-center gap-2">
                                <Mail size={15} className="shrink-0 text-slate-400" />
                                <a
                                    href="mailto:contact@agu.edu.vn"
                                    className="hover:text-[#005baa] transition-colors"
                                >
                                    contact@agu.edu.vn
                                </a>
                            </li>
                            <li className="flex items-center gap-2">
                                <Phone size={15} className="shrink-0 text-slate-400" />
                                <span>(+84) 296 6256565</span>
                            </li>
                        </ul>
                    </div>
                </div>

                {/* Dòng bản quyền dưới cùng */}
                <div className="mt-8 pt-6 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-400">
                    <p>
                        © {new Date().getFullYear()} Trường Đại học An Giang - ĐHQG-HCM. Bảo lưu mọi quyền.
                    </p>
                    <p className="flex items-center gap-1.5">
                        <ShieldCheck size={14} className="text-teal-600" />
                        <span>Phiên bản thử nghiệm học thuật 1.0</span>
                    </p>
                </div>
            </div>
        </footer>
    );
}
