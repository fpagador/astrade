import Swal from 'sweetalert2';
import ApexCharts from 'apexcharts';

// Proportion of people with assigned tasks
export function initUsersTasksChart() {
    if (!window.dashboardRoutes) return;

    const { usersWithoutTasks } = window.dashboardRoutes;
    const usersWithTasksByDay = window.usersWithTasksByDay ?? {};
    const allDays = Object.keys(usersWithTasksByDay);
    if (allDays.length === 0) return;

    const withTasks = Object.values(usersWithTasksByDay);
    const withoutTasks = allDays.map(day => window.usersWithoutTasksByDay?.[day] ?? 0);
    const chartContainer = document.querySelector("#usersTasksProportionChart");
    const weeksSelect = document.querySelector("#weeksFilter");

    function getFilteredData(weeksAhead) {
        const now = new Date();
        now.setHours(0, 0, 0, 0);
        const limitDate = new Date(now);
        limitDate.setDate(now.getDate() + weeksAhead * 7);

        const filteredDays = [];
        const filteredWithTasks = [];
        const filteredWithoutTasks = [];

        allDays.forEach((day, i) => {
            const date = new Date(day);
            date.setHours(0, 0, 0, 0);
            if (date >= now && date <= limitDate) {
                filteredDays.push(day);
                filteredWithTasks.push(withTasks[i]);
                filteredWithoutTasks.push(withoutTasks[i]);
            }
        });

        return { filteredDays, filteredWithTasks, filteredWithoutTasks };
    }

    const { filteredDays, filteredWithTasks, filteredWithoutTasks } = getFilteredData(1);

    const chartOptions = {
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            toolbar: { show: true },
            events: {
                dataPointSelection: (event, chartContext, config) => {
                    const seriesName = config.w.config.series[config.seriesIndex].name;
                    if (seriesName !== 'Sin tareas asignadas') return;

                    const selectedDate = config.w.config.xaxis.categories[config.dataPointIndex];
                    if (!selectedDate) return Swal.fire('Error', 'Día no definido', 'error');

                    const date = new Date(selectedDate);
                    const formattedDate = `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1)
                        .toString()
                        .padStart(2, '0')}/${date.getFullYear()}`;

                    fetch(usersWithoutTasks.replace('__DAY__', selectedDate))
                        .then(res => res.json())
                        .then(data => {
                            const list = data.length
                                ? data.map(u => `<li>${u.name} ${u.surname}</li>`).join('')
                                : '<li>No hay usuarios sin tareas</li>';
                            Swal.fire({
                                title: `Usuarios sin tareas (${formattedDate})`,
                                html: `<ul>${list}</ul>`,
                                width: 600
                            });
                        })
                        .catch(() => Swal.fire('Error', 'No se pudieron cargar los usuarios sin tareas', 'error'));
                }
            }
        },
        series: [
            { name: 'Con tareas asignadas', data: filteredWithTasks },
            { name: 'Sin tareas asignadas', data: filteredWithoutTasks }
        ],
        xaxis: {
            categories: filteredDays,
            labels: {
                rotate: -45,
                formatter: value => {
                    const date = new Date(value);
                    return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1)
                        .toString()
                        .padStart(2, '0')}/${date.getFullYear()}`;
                }
            }
        },
        colors: ['#85C7F2', '#F18605'],
        legend: { show: true },
        yaxis: { title: { text: '% de usuarios' } },
        tooltip: {
            shared: true,
            intersect: false,
            x: {
                formatter: value => {
                    const date = new Date(value);
                    return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1)
                        .toString()
                        .padStart(2, '0')}/${date.getFullYear()}`;
                }
            }
        },
        dataLabels: {
            enabled: true,
            formatter: function (val, opts) {
                const seriesIndex = opts.seriesIndex;
                const total = opts.w.config.series.reduce((acc, s) => acc + s.data[opts.dataPointIndex], 0);
                return total ? `${Math.round((val / total) * 100)}%` : '';
            },
            style: {
                colors: ['#000']
            }
        }
    };

    const chart = new ApexCharts(chartContainer, chartOptions);
    chart.render();

    weeksSelect?.addEventListener('change', e => {
        const weeks = parseInt(e.target.value);
        const { filteredDays, filteredWithTasks, filteredWithoutTasks } = getFilteredData(weeks);
        chart.updateOptions({
            xaxis: { categories: filteredDays },
            series: [
                { name: 'Con tareas asignadas', data: filteredWithTasks },
                { name: 'Sin tareas asignadas', data: filteredWithoutTasks }
            ]
        });
    });
}

// Proportion of tasks completed
export function initTasksProportionChart() {
    const tasksByDay = window.tasksByDayData ?? {};
    const chartContainer = document.querySelector("#tasksProportionChart");
    const weeksSelect = document.querySelector("#weeksBackFilter");
    if (!chartContainer) return;

    function formatDateYMD(date) {
        const y = date.getFullYear();
        const m = String(date.getMonth() + 1).padStart(2, '0');
        const d = String(date.getDate()).padStart(2, '0');
        return `${y}-${m}-${d}`;
    }

    function getTasksData(weeksBack) {
        const daysToShow = weeksBack * 7;
        const today = new Date();
        const filteredDays = [];
        for (let i = 0; i < daysToShow; i++) {
            const date = new Date(Date.UTC(today.getFullYear(), today.getMonth(), today.getDate() - i));
            filteredDays.push(formatDateYMD(date));
        }
        filteredDays.reverse();
        
        const filteredCompleted = filteredDays.map(d => tasksByDay[d]?.completed ?? 0);
        const filteredPending = filteredDays.map(d => tasksByDay[d]?.pending ?? 0);
        const filteredNoTasks = filteredDays.map((d, i) =>
            filteredCompleted[i] === 0 && filteredPending[i] === 0 ? 1 : 0
        );
        return { filteredDays, filteredCompleted, filteredPending, filteredNoTasks };
    }

    let { filteredDays, filteredCompleted, filteredPending, filteredNoTasks } = getTasksData(1);

    const options = {
        chart: { type: 'bar', height: 350, stacked: true, stackType: '100%' },
        series: [
            { name: 'Completadas', data: filteredCompleted },
            { name: 'Pendientes', data: filteredPending },
            { name: 'Sin tareas', data: filteredNoTasks }
        ],
        colors: ['#00B050', '#FFC000', '#DBD9D2'],
        xaxis: {
            categories: filteredDays,
            labels: {
                rotate: -45,
                formatter: value => {
                    const date = new Date(value);
                    return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1)
                        .toString()
                        .padStart(2, '0')}/${date.getFullYear()}`;
                }
            }
        },
        legend: { position: 'bottom' },
        yaxis: { title: { text: '% de tareas' } },
        dataLabels: {
            enabled: true,
            style: {
                colors: ['#000']
            }
        },
    };

    const chart = new ApexCharts(chartContainer, options);
    chart.render();

    weeksSelect?.addEventListener('change', e => {
        const weeks = parseInt(e.target.value);
        const { filteredDays, filteredCompleted, filteredPending, filteredNoTasks } = getTasksData(weeks);
        chart.updateOptions({
            xaxis: { categories: filteredDays },
            series: [
                { name: 'Completadas', data: filteredCompleted },
                { name: 'Pendientes', data: filteredPending },
                { name: 'Sin tareas', data: filteredNoTasks }
            ]
        });
    });
}

// Historical performance of completed tasks
export function initTaskPerformanceHistoryChart() {
    if (!window.dashboardRoutes) return;

    const data = window.taskPerformanceHistory ?? {};
    const chartContainer = document.querySelector("#taskPerformanceHistoryChart");
    const weeksSelect = document.querySelector("#weeksBackPerformanceFilter");
    if (!chartContainer) return;

    const allDays = Object.keys(data).sort();

    function formatDateDMY(dateStr) {
        const d = new Date(dateStr);
        const day = String(d.getDate()).padStart(2, '0');
        const month = String(d.getMonth() + 1).padStart(2, '0');
        const year = d.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function parseLocalDate(dayStr) {
        const [year, month, day] = dayStr.split('-').map(Number);
        return new Date(year, month - 1, day);
    }

    function getFilteredData(weeksBack) {
        const today = new Date();
        today.setHours(0,0,0,0);
        const end = new Date(today);
        const start = new Date(today);
        start.setDate(end.getDate() - (weeksBack * 7) + 1);

        const filteredDays = [];
        const ranges = ['100%', '75-99.9%', '50-74.9%', '<50%', 'Sin tareas'];
        const series = ranges.map(r => ({ name: r, data: [] }));

        for(let d=new Date(start); d<=end; d.setDate(d.getDate()+1)) {
            const dayISO = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
            filteredDays.push(formatDateDMY(dayISO));
            const dayData = data[dayISO] || {};

            let totalUsers = dayData.totalUsers ?? 0;
            if(totalUsers === 0){
                totalUsers = Object.values(dayData).reduce((acc,v)=>typeof v==='number'? acc+v : acc, 0);
            }

            if(totalUsers === 0){
                ['100%','75-99.9%','50-74.9%','<50%'].forEach(r=>series.find(s=>s.name===r).data.push(0));
                series.find(s=>s.name==='Sin tareas').data.push(1); // barra inventada
            } else {
                let sumRanges = 0;
                ['100%','75-99.9%','50-74.9%','<50%'].forEach(r=>{
                    const val = dayData[r] ?? 0;
                    sumRanges += val;
                    series.find(s=>s.name===r).data.push(val);
                });
                const noTasks = Math.max(totalUsers - sumRanges, 0);
                series.find(s=>s.name==='Sin tareas').data.push(noTasks);
            }
        }

        return { filteredDays, series };
    }

    const { filteredDays, series } = getFilteredData(1);

    const options = {
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            stackType: '100%',
            toolbar: { show: false },
            zoom: { enabled: false },
            events: {
                dataPointSelection: async (event, chartContext, config) => {
                    const dayFormatted = config.w.config.xaxis.categories[config.dataPointIndex];
                    const dayISO = allDays.find(d => formatDateDMY(d) === dayFormatted);
                    const total = config.w.globals.stackedSeriesTotals[config.dataPointIndex];

                    const htmlParts = await Promise.all(config.w.config.series.map(async serie => {
                        const val = serie.data[config.dataPointIndex];
                        if (val === 0) return '';

                        const percentage = total ? ((val / total) * 100).toFixed(1) : 0;

                        // Fetch usuarios del backend para este rango y día
                        const users = await fetch(window.dashboardRoutes.usersByPerformance
                            .replace('__DAY__', dayISO)
                            .replace('__RANGE__', encodeURIComponent(serie.name))
                        )
                            .then(res => res.json())
                            .then(userList => userList.length
                                ? userList.map(u => `<li>${u.name} ${u.surname}</li>`).join('')
                                : '<li>No hay usuarios</li>'
                            )
                            .catch(() => '<li>Error cargando usuarios</li>');

                        return `
            <div style="display:flex; align-items:flex-start; justify-content:space-between; margin-bottom:8px;">
              <div style="width:100px; text-align:center;"><strong>${percentage}%</strong></div>
              <ul style="margin:0; padding-left:0; list-style:none;">${users}</ul>
            </div>
            <hr/>
        `;
                    }));

                    Swal.fire({
                        title: `<div style="text-align:center;">${dayFormatted}</div>`,
                        html: htmlParts.join(''),
                        width: 700,
                        customClass: { popup: 'p-3' },
                    });
                }
            }
        },
        series,
        xaxis: { categories: filteredDays },
        colors: ['#00B050', '#92D050', '#FFC000', '#C00000', '#DBD9D2'],
        legend: { position: 'bottom' },
        yaxis: { title: { text: '% de usuarios' } },
        tooltip: { shared: true, intersect: false, x: { formatter: val => val } },
        dataLabels: {
            enabled: true,
            formatter: function(val, opts){
                const total = opts.w.globals.stackedSeriesTotals[opts.dataPointIndex];
                if(total===1 && opts.seriesIndex===4) return '100%';
                return total ? `${Math.round((val/total)*100)}%` : '';
            },
            style: { colors: ['#000'] }
        },
        stroke: { curve: 'smooth' }
    };

    const chart = new ApexCharts(chartContainer, options);
    chart.render();

    weeksSelect?.addEventListener('change', e => {
        const weeks = parseInt(e.target.value);
        const { filteredDays, series } = getFilteredData(weeks);
        chart.updateOptions({ xaxis: { categories: filteredDays }, series });
    });
}

// Employees by company
export function initEmployeesByCompanyChart() {
    const employeesByCompany = window.employeesByCompany ?? [];
    if (employeesByCompany.length === 0) return;

    const { employeesByCompanyRoute } = window.dashboardRoutes ?? {};

    const companyNames = employeesByCompany.map(e => e.company ? e.company.name : 'Sin empresa');
    const companyIds = employeesByCompany.map(e => e.company ? e.company.id : null);
    const totals = employeesByCompany.map(e => e.total);

    // Creamos dos series: con empresa y sin empresa
    const seriesWithCompany = totals.map((total, i) => companyNames[i] !== 'Sin empresa' ? total : 0);
    const seriesWithoutCompany = totals.map((total, i) => companyNames[i] === 'Sin empresa' ? total : 0);

    const options = {
        chart: {
            type: 'bar',
            height: 350,
            stacked: true,
            events: {
                dataPointSelection: (event, chartContext, config) => {
                    const companyId = companyIds[config.dataPointIndex];
                    const companyName = companyNames[config.dataPointIndex];

                    fetch(employeesByCompanyRoute.replace('__ID__', companyId ?? ''))
                        .then(res => res.json())
                        .then(data => {
                            const list = data.length
                                ? data.map(u => `<li>${u.name} ${u.surname}</li>`).join('')
                                : '<li>No hay empleados registrados</li>';
                            Swal.fire({
                                title: `Empleados en ${companyName}`,
                                html: `<ul>${list}</ul>`,
                                width: 600
                            });
                        })
                        .catch(() => Swal.fire('Error', 'No se pudieron cargar los empleados', 'error'));
                }
            }
        },
        series: [
            { name: 'Con empresa', data: seriesWithCompany },
            { name: 'Sin empresa', data: seriesWithoutCompany }
        ],
        xaxis: { categories: companyNames },
        colors: ['#4F46E5', '#DBD9D2']
    };

    new ApexCharts(document.querySelector("#employeesByCompanyChart"), options).render();
}
