</div> <!-- Close main-content -->

<script>
// Auto-hide alerts
setTimeout(function() {
    document.querySelectorAll('.alert').forEach(el => {
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 500);
    });
}, 5000);
</script>
</body>
</html>