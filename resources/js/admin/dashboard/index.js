import {
    Chart,
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    BarController,
    BarElement,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend,
} from 'chart.js';

Chart.register(
    LineController,
    LineElement,
    PointElement,
    CategoryScale,
    LinearScale,
    BarController,
    BarElement,
    DoughnutController,
    ArcElement,
    Tooltip,
    Legend
);

/*
|--------------------------------------------------------------------------
| Dashboard Charts
|--------------------------------------------------------------------------
*/

document.addEventListener('DOMContentLoaded', () => {
    /*
    |--------------------------------------------------------------------------
    | Sales Trend
    |--------------------------------------------------------------------------
    */

    const salesTrendCanvas =
        document.getElementById('salesTrendChart');

    const salesTrendData =
        document.getElementById('salesTrendData');

    if (salesTrendCanvas && salesTrendData) {

        const data =
            JSON.parse(
                salesTrendData.textContent
            );

        /*
        |--------------------------------------------------------------------------
        | Formatters
        |--------------------------------------------------------------------------
        */

        const numberFormatter =
            new Intl.NumberFormat('id-ID');

        const currencyFormatter =
            new Intl.NumberFormat(
                'id-ID',
                {
                    style: 'currency',
                    currency: 'IDR',
                    maximumFractionDigits: 0,
                }
            );

        /*
        |--------------------------------------------------------------------------
        | Labels
        |--------------------------------------------------------------------------
        */

        const labels =
            data.map(item => {

                const date =
                    new Date(`${item.date}T00:00:00`);

                return new Intl.DateTimeFormat(
                    'id-ID',
                    {
                        day: '2-digit',
                        month: 'short',
                    }
                ).format(date);

            });

        /*
        |--------------------------------------------------------------------------
        | Dataset
        |--------------------------------------------------------------------------
        */

        const revenue =
            data.map(
                item => Number(item.revenue)
            );

        const orders =
            data.map(
                item => Number(item.total_orders)
            );

        const products =
            data.map(
                item => Number(item.total_products_sold)
            );

        /*
        |--------------------------------------------------------------------------
        | Chart
        |--------------------------------------------------------------------------
        */

        new Chart(
            salesTrendCanvas,
            {
                type: 'line',

                data: {

                    labels,

                    datasets: [ 
                        { 
                            label: 'Pendapatan', 
                            data: revenue, 
                            yAxisID: 'y',
                            tension: 0.35, 
                            borderColor: '#16A34A', 
                            backgroundColor: '#16A34A', 
                            borderWidth: 2.5, 
                            pointBackgroundColor: '#16A34A', 
                            pointBorderColor: '#FFFFFF', 
                            pointBorderWidth: 2, 
                            pointRadius: 4, 
                            pointHoverRadius: 6, 
                            fill: false, 
                        }, 
                        { 
                            label: 'Pesanan', 
                            data: orders, 
                            yAxisID: 'y1', 
                            tension: 0.35, 
                            borderColor: '#2563EB', 
                            backgroundColor: '#2563EB', 
                            borderWidth: 2.5, 
                            pointBackgroundColor: '#2563EB', 
                            pointBorderColor: '#FFFFFF', 
                            pointBorderWidth: 2, 
                            pointRadius: 4, 
                            pointHoverRadius: 6, 
                            fill: false, 
                        }, 
                        { 
                            label: 'Produk Terjual', 
                            data: products, 
                            yAxisID: 'y1', 
                            tension: 0.35, 
                            borderColor: '#9333EA', 
                            backgroundColor: '#9333EA', 
                            borderWidth: 2.5, 
                            pointBackgroundColor: '#9333EA', 
                            pointBorderColor: '#FFFFFF', 
                            pointBorderWidth: 2, 
                            pointRadius: 4, 
                            pointHoverRadius: 6, 
                            fill: false, 
                        }, 
                    ],
                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    interaction: {

                        mode: 'index',

                        intersect: false,
                    },

                    plugins: {

                        /*
                        |--------------------------------------------------------------------------
                        | Legend
                        |--------------------------------------------------------------------------
                        */

                        legend: {

                            position: 'top',
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | Tooltip
                        |--------------------------------------------------------------------------
                        */

                        tooltip: {

                            callbacks: {

                                title(context) {

                                    const index =
                                        context[0].dataIndex;

                                    const date =
                                        data[index]?.date;

                                    if (!date) {
                                        return '';
                                    }

                                    const parsedDate =
                                        new Date(
                                            `${date}T00:00:00`
                                        );

                                    return new Intl.DateTimeFormat(
                                        'id-ID',
                                        {
                                            day: '2-digit',
                                            month: 'long',
                                            year: 'numeric',
                                        }
                                    ).format(parsedDate);
                                },

                                label(context) {

                                    const label =
                                        context.dataset.label || '';

                                    const value =
                                        context.parsed.y ?? 0;

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Revenue
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        label === 'Pendapatan'
                                    ) {

                                        return `${label}: ${currencyFormatter.format(value)}`;
                                    }

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Orders
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        label === 'Pesanan'
                                    ) {

                                        return `${label}: ${numberFormatter.format(value)} order`;
                                    }

                                    /*
                                    |--------------------------------------------------------------------------
                                    | Products
                                    |--------------------------------------------------------------------------
                                    */

                                    if (
                                        label === 'Produk Terjual'
                                    ) {

                                        return `${label}: ${numberFormatter.format(value)} produk`;
                                    }

                                    return `${label}: ${numberFormatter.format(value)}`;
                                },
                            },
                        },
                    },

                    /*
                    |--------------------------------------------------------------------------
                    | Scales
                    |--------------------------------------------------------------------------
                    */

                    scales: {

                        /*
                        |--------------------------------------------------------------------------
                        | Revenue Axis
                        |--------------------------------------------------------------------------
                        */

                        y: {

                            type: 'linear',

                            position: 'left',

                            beginAtZero: true,

                            title: {

                                display: true,

                                text: 'Pendapatan',
                            },

                            ticks: {

                                callback(value) {

                                    return 'Rp ' +
                                        new Intl.NumberFormat(
                                            'id-ID',
                                            {
                                                notation: 'compact',

                                                maximumFractionDigits: 1,
                                            }
                                        ).format(value);
                                },
                            },
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | Quantity Axis
                        |--------------------------------------------------------------------------
                        */

                        y1: {

                            type: 'linear',

                            position: 'right',

                            beginAtZero: true,

                            title: {

                                display: true,

                                text: 'Jumlah',
                            },

                            grid: {

                                drawOnChartArea: false,
                            },

                            ticks: {

                                precision: 0,
                            },
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | X Axis
                        |--------------------------------------------------------------------------
                        */

                        x: {

                            ticks: {

                                maxRotation: 0,

                                minRotation: 0,

                                autoSkip: true,

                                maxTicksLimit: 10,
                            },
                        },
                    },
                },
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Sales By Category
    |--------------------------------------------------------------------------
    */

    const categoryCanvas =
        document.getElementById('salesByCategoryChart');

    const categoryData =
        document.getElementById('salesByCategoryData');

    if (categoryCanvas && categoryData) {

        const data =
            JSON.parse(
                categoryData.textContent
            );

        /*
        |--------------------------------------------------------------------------
        | Formatter
        |--------------------------------------------------------------------------
        */

        const numberFormatter =
            new Intl.NumberFormat('id-ID');

        /*
        |--------------------------------------------------------------------------
        | Labels
        |--------------------------------------------------------------------------
        */

        const labels =
            data.map(
                item =>
                    item.category_name ??
                    'Tanpa Kategori'
            );

        /*
        |--------------------------------------------------------------------------
        | Quantities
        |--------------------------------------------------------------------------
        */

        const quantities =
            data.map(
                item =>
                    Number(item.total_quantity) || 0
            );

        /*
        |--------------------------------------------------------------------------
        | Total Quantity
        |--------------------------------------------------------------------------
        */

        const totalQuantity =
            quantities.reduce(
                (total, value) =>
                    total + value,
                0
            );

        /*
        |--------------------------------------------------------------------------
        | Colors
        |--------------------------------------------------------------------------
        */

        const colors = [
            '#3B82F6',
            '#8B5CF6',
            '#10B981',
            '#F59E0B',
            '#EF4444',
            '#06B6D4',
            '#EC4899',
            '#84CC16',
        ];

        /*
        |--------------------------------------------------------------------------
        | Chart
        |--------------------------------------------------------------------------
        */

        new Chart(
            categoryCanvas,
            {
                type: 'doughnut',

                data: {

                    labels,

                    datasets: [

                        {
                            label: 'Produk Terjual',

                            data: quantities,

                            backgroundColor:
                                data.map(
                                    (_, index) =>
                                        colors[
                                            index %
                                            colors.length
                                        ]
                                ),

                            borderColor: '#ffffff',

                            borderWidth: 2,

                            hoverOffset: 6,
                        },

                    ],
                },

                options: {

                    responsive: true,

                    maintainAspectRatio: false,

                    cutout: '62%',

                    plugins: {

                        /*
                        |--------------------------------------------------------------------------
                        | Legend
                        |--------------------------------------------------------------------------
                        */

                        legend: {

                            position: 'right',

                            labels: {

                                usePointStyle: true,

                                pointStyle: 'circle',

                                padding: 15,

                                boxWidth: 10,
                            },
                        },

                        /*
                        |--------------------------------------------------------------------------
                        | Tooltip
                        |--------------------------------------------------------------------------
                        */

                        tooltip: {

                            callbacks: {

                                label(context) {

                                    const value =
                                        Number(
                                            context.parsed
                                        ) || 0;

                                    const percentage =
                                        totalQuantity > 0
                                            ? (
                                                value /
                                                totalQuantity
                                            ) * 100
                                            : 0;

                                    return [
                                        `${context.label}`,
                                        `Terjual: ${numberFormatter.format(value)} produk`,
                                        `Persentase: ${percentage.toFixed(1)}%`,
                                    ];
                                },
                            },
                        },
                    },
                },
            }
        );
    }
});