<?php include 'includes/header.php'; ?>
<style>
.about-hero {
    background: linear-gradient(135deg, #007BFF 60%, #0056b3 100%);
    color: #fff;
    padding: 3rem 1rem 2rem 1rem;
    border-radius: 0 0 2rem 2rem;
    text-align: center;
    margin-bottom: 2rem;
    box-shadow: 0 4px 24px rgba(0,123,255,0.08);
    position: relative;
}
.about-hero-img {
    width: 140px;
    height: 140px;
    object-fit: cover;
    border-radius: 50%;
    border: 6px solid #fff;
    box-shadow: 0 4px 16px rgba(0,123,255,0.12);
    margin-top: -70px;
    background: #fff;
}
@media (max-width: 600px) {
    .about-hero-img { width: 90px; height: 90px; margin-top: -45px; }
    .about-hero { padding: 2rem 0.5rem 1.2rem 0.5rem; }
}
.about-card {
    background: #fff;
    border-radius: 1.2rem;
    box-shadow: 0 2px 8px rgba(0,123,255,0.08);
    padding: 2rem 1.5rem 1.5rem 1.5rem;
    margin: 2rem auto 1.5rem auto;
    max-width: 500px;
    text-align: center;
}
.about-card h3 {
    color: #007BFF;
    font-weight: 600;
    margin-bottom: 0.7rem;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    font-size: 1.3rem;
}
.about-card i {
    color: #007BFF;
    font-size: 1.4em;
}
</style>
<main class="container text-center" style="max-width:700px;">
    <div class="about-hero">
        <h2 style="font-size:2.3rem;font-weight:700;letter-spacing:1px;margin-bottom:0.5rem;">About Us</h2>
        <p style="font-size:1.1rem;max-width:500px;margin:0 auto 1.5rem auto;">Jaffna Services is your trusted platform to find and book reliable local service providers in Jaffna, Sri Lanka.</p>
    </div>
    <img src="https://images.unsplash.com/photo-1506744038136-46273834b3fb?auto=format&fit=crop&w=800&q=80" alt="Jaffna Services" class="about-hero-img">
    <section class="about-card">
        <h3><i class="fa fa-bullseye"></i> Our Mission</h3>
        <p>To connect the people of Jaffna with trusted, skilled local service providers, making it easy and safe to book essential services for homes and businesses.</p>
    </section>
    <section class="about-card">
        <h3><i class="fa fa-eye"></i> Our Vision</h3>
        <p>To be the most reliable and user-friendly platform for service bookings in Jaffna, empowering both customers and local professionals to thrive in a digital world.</p>
    </section>
    <a href="contact.php" class="btn mt-2" style="font-size:1.1rem;padding:0.9rem 2.2rem;">Contact Us</a>
</main>
<?php include 'includes/footer.php'; ?> 