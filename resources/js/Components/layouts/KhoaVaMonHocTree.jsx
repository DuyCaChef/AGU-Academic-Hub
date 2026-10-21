import { useState } from "react";
import { Link } from "@inertiajs/react";
import { ChevronDown, FolderKanban } from "lucide-react";

export default function FacultyCourseTree({ faculities = [] }) {
    const [expandedFaculty, setExpandedFaculty] = useState(null);
    const toggleFaculty = (makhoa) => {
        setExpandedFaculty((current) => (current === makhoa ? null : makhoa));
    };
};
return (
    <div className="space-y-3">
        <div className="flex items-center justify-between mb-1">
            <p className="text-xs font-semibold uppercase">
                Khoa / bộ môn
            </p>
            <span className="text-xs text-gray-500">
                {faculities.length} Khoa
            </span>
        </div>
        {faculities.length === 0 ? (
            <p className="text-sm text-gray-500">
                Chưa có dữ liệu khoa...
            </p>
        ) : (
            faculities.map((khoa) => (
                <div key={khoa.makhoa}>
                    <button type="button"
                        onClick={() => toggleFaculty(khoa.ma_khoa)}
                        className="flex w-full items-center justify-between rounded-lg p-2 text-left hover:bg-gray-100"
                    >
                        <span className="flex items-center gap-2">
                            <FolderKanban size={18} />
                            <span>{khoa.ten_khoa}</span>
                        </span>
                        <ChevronDown size={16}
                            className={expandedFaculty === khoa.ma_khoa
                                ? "rotate-180 transition-transform"
                                : "transition-transform"
                            }
                        />
                    </button>
                    {expandedFaculty === khoa.ma_khoa && (
                        <div className="ml-4 mt-2 space-y-1">
                            {khoa.mon_hoc?.length > 0 ? (
                                khoa.mon_hoc.map(mon => (
                                    <Link key={mon.ma_mon_hoc}
                                        href={route("courses.show",
                                            mon.ma_mon_hoc,
                                        )
                                        }
                                        className="block rouded-lg p-2 text-sm hover:bg-gray-100"
                                    >
                                        <span className="block">
                                            {mon.ten_mon_hoc}
                                        </span>
                                        <span className="text-xs text-gray-500">
                                            {mon.ma_mon_hoc}
                                        </span>
                                    </Link>
                                ))
                            ) : (
                                <p className="p-2 text-xs text-gray-500">Chưa có môn học</p>
                            )}
                        </div>
                    )}
                </div>
            ))
        )}
    </div>
);
