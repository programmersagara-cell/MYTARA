/**
 * Dashboard Charts
 */

(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', function() {
        if (typeof window.chartData === 'undefined') return;

        const colors = {
            blue: '#3B82F6',
            violet: '#8B5CF6',
            green: '#10B981',
            amber: '#F59E0B',
            pink: '#EC4899',
            indigo: '#6366F1',
            gray: '#6B7280',
            cyan: '#06B6D4',
            red: '#EF4444',
        };

        // ─── Assets by Type (Doughnut) ───
        const typeCtx = document.getElementById('typeChart');
        if (typeCtx && window.chartData.byType) {
            new Chart(typeCtx, {
                type: 'doughnut',
                data: {
                    labels: window.chartData.byType.map(d => d.type.charAt(0).toUpperCase() + d.type.slice(1)),
                    datasets: [{
                        data: window.chartData.byType.map(d => d.count),
                        backgroundColor: [colors.blue, colors.violet, colors.green, colors.amber, colors.pink, colors.indigo, colors.gray],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 12, usePointStyle: true, font: { size: 11 } }
                        }
                    },
                    cutout: '65%',
                }
            });
        }

        // ─── Assets by Status (Doughnut) ───
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx && window.chartData.byStatus) {
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: window.chartData.byStatus.map(d => d.status.charAt(0).toUpperCase() + d.status.slice(1)),
                    datasets: [{
                        data: window.chartData.byStatus.map(d => d.count),
                        backgroundColor: [colors.green, colors.gray, colors.amber, colors.red, colors.indigo, colors.cyan],
                        borderWidth: 0,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 12, usePointStyle: true, font: { size: 11 } }
                        }
                    },
                    cutout: '65%',
                }
            });
        }

        // ─── Assets by Department (Bar) ───
        const deptCtx = document.getElementById('deptChart');
        if (deptCtx && window.chartData.byDept) {
            new Chart(deptCtx, {
                type: 'bar',
                data: {
                    labels: window.chartData.byDept.map(d => d.department || 'Unassigned'),
                    datasets: [{
                        label: 'Assets',
                        data: window.chartData.byDept.map(d => d.count),
                        backgroundColor: colors.blue,
                        borderRadius: 4,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 10 } },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            ticks: { font: { size: 10 } },
                            grid: { display: false }
                        }
                    }
                }
            });
        }

        // ─── Monthly Additions (Line) ───
        const monthlyCtx = document.getElementById('monthlyChart');
        if (monthlyCtx && window.chartData.monthly) {
            new Chart(monthlyCtx, {
                type: 'line',
                data: {
                    labels: window.chartData.monthly.map(d => {
                        const [year, month] = d.month.split('-');
                        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                        return months[parseInt(month) - 1] + ' ' + year;
                    }),
                    datasets: [{
                        label: 'New Assets',
                        data: window.chartData.monthly.map(d => d.count),
                        borderColor: colors.violet,
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: colors.violet,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 10 } },
                            grid: { color: 'rgba(0,0,0,0.05)' }
                        },
                        x: {
                            ticks: { font: { size: 10 } },
                            grid: { display: false }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    }
                }
            });
        }
    });

})();
