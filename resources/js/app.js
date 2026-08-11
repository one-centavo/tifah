import { initFlowbite } from "flowbite";
import Chart from "chart.js/auto";

window.Chart = Chart;

document.addEventListener("livewire:navigated", () => {
    initFlowbite();
});

