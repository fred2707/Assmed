
// document.addEventListener('DOMContentLoaded', () => {
//   const loginForm = document.getElementById('loginForm');
//   if (loginForm) {
//     loginForm.addEventListener('submit', async (e) => {
//       e.preventDefault();
//       const email = document.getElementById('email').value;
//       const password = document.getElementById('password').value;
//       const userType = document.getElementById('userType').value;
//       const res = await fetch('http://localhost:5000/api/auth/login', {
//         method: 'POST',
//         headers: { 'Content-Type': 'application/json' },
//         body: JSON.stringify({ email, password, userType })
//       });
//       const data = await res.json();
//       alert(data.message || 'Connexion réussie');
//     });
//   }

//   const chatForm = document.getElementById('chatForm');
//   if (chatForm) {
//     chatForm.addEventListener('submit', async (e) => {
//       e.preventDefault();
//       const doctorId = document.getElementById('doctorId').value;
//       const patientId = document.getElementById('patientId').value;
//       const preferredDate = document.getElementById('preferredDate').value;
//       const res = await fetch('http://localhost:5000/api/appointments/book', {
//         method: 'POST',
//         headers: { 'Content-Type': 'application/json' },
//         body: JSON.stringify({ doctorId, patientId, preferredDate })
//       });
//       const data = await res.json();
//       document.getElementById('responseBox').innerText = JSON.stringify(data, null, 2);
//     });
//   }

//   const availabilityForm = document.getElementById('availabilityForm');
//   if (availabilityForm) {
//     availabilityForm.addEventListener('submit', async (e) => {
//       e.preventDefault();
//       const doctorId = document.getElementById('doctorId').value;
//       const day = document.getElementById('day').value;
//       const startHour = document.getElementById('startHour').value;
//       const endHour = document.getElementById('endHour').value;
//       const res = await fetch(`http://localhost:5000/api/doctors/${doctorId}/availability`, {
//         method: 'PUT',
//         headers: { 'Content-Type': 'application/json' },
//         body: JSON.stringify({ availability: [{ day, startHour, endHour }] })
//       });
//       const data = await res.json();
//       alert('Disponibilité mise à jour !');
//     });
//   }
// });




//ce que j'ai fait
document.getElementById('login-form').addEventListener('submit', function (e) {
  const email = document.getElementById('email').value.trim();
  const password = document.getElementById('password').value.trim();
  const emailError = document.getElementById('email-error');
  const passwordError = document.getElementById('password-error');
  let isValid = true;

  // Reset error messages
  emailError.textContent = '';
  passwordError.textContent = '';

  // Email validation
  const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!email) {
    emailError.textContent = 'L\'email est requis.';
    isValid = false;
  } else if (!emailPattern.test(email)) {
    emailError.textContent = 'Veuillez entrer un email valide.';
    isValid = false;
  }

  // Password validation
  if (!password) {
    passwordError.textContent = 'Le mot de passe est requis.';
    isValid = false;
  }

  if (!isValid) {
    e.preventDefault(); // Prevent form submission if validation fails
  }
});