<div class="container">
  <?php $Controller->view('transaction/topbar', $data); ?>
  <div>
    <canvas id="cashflowChart"></canvas>
    <canvas id="pemasukanKelompokChart"></canvas>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  document.querySelector("#dash-tab").classList.add("active");
  fetch('/api/cashflow-summary.json')
    .then(res => res.json())
    .then(data => {
      const ctx = document.getElementById('cashflowChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.bulan,
          datasets: [{
              label: 'Pemasukan',
              data: data.pemasukan,
              borderColor: 'green',
              fill: false
            },
            {
              label: 'Pengeluaran',
              data: data.pengeluaran,
              borderColor: 'red',
              fill: false
            },
            {
              label: 'Net Cashflow',
              data: data.net_cashflow,
              borderColor: 'blue',
              borderDash: [5, 5],
              fill: false
            }
          ]
        },
        options: {
          responsive: true,
          plugins: {
            legend: {
              position: 'top'
            },
            title: {
              display: true,
              text: 'Arus Kas Bulanan'
            }
          },
          scales: {
            y: {
              beginAtZero: true
            }
          }
        }
      });
    });
  fetch('/Main/dashboard?')
    .then(res => res.json())
    .then(data => {
      const ctx = document.getElementById('pemasukanKelompokChart').getContext('2d');
      new Chart(ctx, {
        type: 'line',
        data: {
          labels: data.bulan,
          datasets: data.datasets
        },
        options: {
          responsive: true,
          plugins: {
            title: {
              display: true,
              text: 'Pemasukan per Kelompok (Stacked)'
            }
          },
          scales: {
            y: {
              stacked: true
            },
            x: {
              stacked: true
            }
          }
        }
      });
    });
</script>