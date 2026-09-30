/**
 * ChartPanel — POO PROTOTÍPICA (Object.create), no mesmo estilo do Toast.
 *
 * Envolve o Chart.js (carregado por CDN e exposto como `window.Chart`) para que
 * nenhuma tela precise conhecer a API dele. Os métodos moram uma única vez em
 * `chartPanelPrototype`; cada painel criado por `createChartPanel()` apenas
 * delega ao mesmo protótipo:
 *
 *      Object.getPrototypeOf(a) === Object.getPrototypeOf(b)   // true
 *      a.bar === b.bar                                          // true
 *
 * Próprio de cada objeto: `palette`, `charts` (as instâncias já desenhadas) e
 * `logger`. Compartilhado: bar, stackedBar, doughnut, draw, destroy e clear.
 */

/** Paleta com o mesmo tom terroso da identidade visual do sistema. */
export const DEFAULT_PALETTE = Object.freeze([
    "#8c6a4f",
    "#c9a227",
    "#3d2c23",
    "#a8846b",
    "#6b8e7f",
    "#b5651d",
    "#7a6a60",
    "#d9cec6"
]);

export const chartPanelPrototype = {
    /** A biblioteca só é exigida na hora de desenhar, nunca na importação. */
    library() {
        return typeof window !== "undefined" ? window.Chart : undefined;
    },

    isAvailable() {
        return typeof this.library() === "function";
    },

    color(index) {
        return this.palette[index % this.palette.length];
    },

    /** Descarta o gráfico anterior daquele canvas antes de redesenhar. */
    destroy(canvasId) {
        const previous = this.charts.get(canvasId);

        if (previous) {
            previous.destroy();
            this.charts.delete(canvasId);
        }

        return this;
    },

    clear() {
        [...this.charts.keys()].forEach((id) => this.destroy(id));
        return this;
    },

    /**
     * Desenha um gráfico em um <canvas>.
     * Devolve a instância do Chart.js, ou null quando o canvas ou a biblioteca
     * não estão disponíveis — a tela segue funcionando sem os gráficos.
     */
    draw(canvasId, config) {
        const canvas = document.getElementById(canvasId);

        if (!canvas) {
            return null;
        }

        if (!this.isAvailable()) {
            this.logger.warn(`Chart.js não carregou: o gráfico "${canvasId}" não será exibido.`);
            return null;
        }

        this.destroy(canvasId);

        const Chart = this.library();
        const chart = new Chart(canvas, config);

        this.charts.set(canvasId, chart);

        return chart;
    },

    /** Barras verticais de uma única série. */
    bar(canvasId, { labels, data, label = "Total" }) {
        return this.draw(canvasId, {
            type: "bar",
            data: {
                labels,
                datasets: [{
                    label,
                    data,
                    backgroundColor: labels.map((_, index) => this.color(index)),
                    borderRadius: 6
                }]
            },
            options: {
                ...this.baseOptions(),
                plugins: { legend: { display: false }, tooltip: { enabled: true } },
                scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
            }
        });
    },

    /** Barras empilhadas: usado para "ativos x inativos", "publicadas x rascunho". */
    stackedBar(canvasId, { labels, datasets }) {
        return this.draw(canvasId, {
            type: "bar",
            data: {
                labels,
                datasets: datasets.map((dataset, index) => ({
                    ...dataset,
                    backgroundColor: dataset.backgroundColor ?? this.color(index),
                    borderRadius: 4
                }))
            },
            options: {
                ...this.baseOptions(),
                scales: {
                    x: { stacked: true },
                    y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    },

    /** Rosca, para participação percentual. */
    doughnut(canvasId, { labels, data, label = "Total" }) {
        return this.draw(canvasId, {
            type: "doughnut",
            data: {
                labels,
                datasets: [{
                    label,
                    data,
                    backgroundColor: labels.map((_, index) => this.color(index)),
                    borderWidth: 0
                }]
            },
            options: {
                ...this.baseOptions(),
                cutout: "58%"
            }
        });
    },

    baseOptions() {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: "bottom", labels: { boxWidth: 12, font: { size: 11 } } }
            }
        };
    }
};

/** Fábrica: cria um painel que delega ao protótipo compartilhado. */
export function createChartPanel({ palette = DEFAULT_PALETTE, logger = console } = {}) {
    const panel = Object.create(chartPanelPrototype);

    panel.palette = palette;
    panel.logger = logger;
    panel.charts = new Map();

    return panel;
}

export default createChartPanel;
