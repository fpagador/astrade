import Swal from 'sweetalert2';
import ApexCharts from 'apexcharts';

document.addEventListener('DOMContentLoaded', function () {
    if (!window.dashboardRoutes) return;
    // --- ROUTES ---
    const { tasksByDay, usersWithoutTasks, employeesByCompanyRoute } = window.dashboardRoutes;

    // --- TASKS BY DAY ---
    const tasksByDayData = window.tasksByDayData;
    if (tasksByDayData && Object.keys(tasksByDayData).length > 0) {
        const categories = Object.keys(tasksByDayData);
        const rawData = Object.values(tasksByDayData);

        const data = rawData.map(v => v === 0 ? 0.1 : v);
        const customColors = [
            '#3B82F6',
            '#F05151',
            '#F7E592',
            '#B173E6',
            '#A3F792',
            '#CBC5D1',
            '#E8A576'
        ];

        const colors = rawData.map((val, index) =>
            val === 0 ? '#F59E0B' : customColors[index % customColors.length]
        );

        const tasksByDayOptions = {
            chart: {
                type: 'bar',
                height: 350,
                toolbar: { show: true },
                events: {
                    dataPointSelection: function (event, chartContext, config) {
                        const day = categories[config.dataPointIndex];
                        if (!day) return Swal.fire('Error', 'Día no definido', 'error');

                        fetch(tasksByDay.replace('__DAY__', day))
                            .then(res => {
                                if (!res.ok) throw new Error('Error al obtener tareas');
                                return res.json();
                            })
                            .then(data => {
                                const list = data.length
                                    ? data.map(t => `<li>${t.title} — <strong>${t.user.name} ${t.user.surname}</strong></li>`).join('')
                                    : '<li>No hay tareas</li>';
                                Swal.fire({
                                    title: `Tareas del ${day}`,
                                    html: `<ul>${list}</ul>`,
                                    width: 600
                                });
                            })
                            .catch(() => Swal.fire('Error', 'No se pudieron cargar las tareas', 'error'));
                    }
                }
            },
            series: [{
                name: 'Tareas',
                data: data
            }],
            xaxis: {
                categories: categories,
                labels: { rotate: -45 }
            },
            plotOptions: {
                bar: {
                    columnWidth: '55%',
                    distributed: true,
                    dataLabels: {
                        position: 'center'
                    }
                }
            },
            dataLabels: {
                enabled: true,
                formatter: (val, opts) => {
                    const realVal = rawData[opts.dataPointIndex];
                    return realVal === 0 ? '0' : realVal;
                },
                style: {
                    colors: ['#fff'],
                    fontWeight: 600
                },
                offsetY: 0
            },
            tooltip: {
                y: { formatter: val => Math.round(val) + " tareas" }
            },
            colors: colors,
            fill: {
                type: 'solid',
                opacity: 1
            },
            yaxis: {
                min: 0,
                forceNiceScale: true
            }
        };
        new ApexCharts(document.querySelector("#tasksByDayChart"), tasksByDayOptions).render();
    }

    // --- USERS WITHOUT TASKS ---
    const usersWithoutTasksCount = window.usersWithoutTasksCount ?? 0;
    const usersWithoutTasksOptions = {
        chart: {
            type: 'bar',
            height: 350,
            events: {
                dataPointSelection: function () {
                    fetch(usersWithoutTasks)
                        .then(res => {
                            if (!res.ok) throw new Error('Error al obtener usuarios');
                            return res.json();
                        })
                        .then(data => {
                            const list = data.length
                                ? data.map(u => `<li>${u.name} ${u.surname}</li>`).join('')
                                : '<li>No hay usuarios sin tareas</li>';
                            Swal.fire({
                                title: 'Usuarios sin tareas',
                                html: `<ul>${list}</ul>`,
                                width: 600
                            });
                        })
                        .catch(() => Swal.fire('Error', 'No se pudieron cargar los usuarios sin tareas', 'error'));
                }
            }
        },
        series: [{
            name: 'Usuarios sin tareas',
            data: [usersWithoutTasksCount]
        }],
        xaxis: { categories: ['Sin tareas'] },
        colors: ['#F59E0B']
    };
    new ApexCharts(document.querySelector("#usersWithoutTasksChart"), usersWithoutTasksOptions).render();

    // --- EMPLOYEES BY COMPANY ---
    const employeesByCompany = window.employeesByCompany ?? [];
    if (employeesByCompany.length > 0) {
        const companyNames = employeesByCompany.map(e => e.company ? e.company.name : 'Sin empresa');
        const companyIds = employeesByCompany.map(e => e.company ? e.company.id : null);
        const totals = employeesByCompany.map(e => e.total);
        const employeesByCompanyOptions = {
            chart: {
                type: 'bar',
                height: 350,
                events: {
                    dataPointSelection: function (event, chartContext, config) {
                        const companyId = companyIds[config.dataPointIndex];
                        const companyName = companyNames[config.dataPointIndex];
                        fetch(employeesByCompanyRoute.replace('__ID__', companyId ?? ''))
                            .then(res => {
                                if (!res.ok) throw new Error('Error al obtener empleados');
                                return res.json();
                            })
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
            series: [{ name: 'Empleados', data: totals }],
            xaxis: { categories: companyNames },
            colors: ['#4F46E5']
        };
        new ApexCharts(document.querySelector("#employeesByCompanyChart"), employeesByCompanyOptions).render();
    }
});
