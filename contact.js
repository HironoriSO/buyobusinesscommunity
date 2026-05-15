// Form validation and submission handler
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('contactForm');
  const submitBtn = document.getElementById('submitBtn');
  const alertsContainer = document.getElementById('alerts-container');

  // Validate individual fields
  const validateField = (field) => {
    const value = field.value.trim();
    
    if (field.hasAttribute('required') && !value) {
      return {
        valid: false,
        message: `${field.labels[0].textContent.replace('*', '').trim()}は必須項目です。`
      };
    }

    if (field.type === 'email' && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      if (!emailRegex.test(value)) {
        return {
          valid: false,
          message: '有効なメールアドレスを入力してください。'
        };
      }
    }

    if (field.type === 'tel' && value) {
      const phoneRegex = /^[\d\-\s()+]*$/;
      if (!phoneRegex.test(value)) {
        return {
          valid: false,
          message: '有効な電話番号を入力してください。'
        };
      }
    }

    return { valid: true };
  };

  // Show alert message
  const showAlert = (message, type = 'info') => {
    const alert = document.createElement('div');
    alert.className = `alert ${type}`;
    alert.textContent = message;
    alertsContainer.innerHTML = '';
    alertsContainer.appendChild(alert);
    
    // Scroll to alert
    alert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    
    // Auto-remove error after 5 seconds
    if (type === 'error') {
      setTimeout(() => alert.remove(), 5000);
    }
  };

  // Real-time field validation
  form.querySelectorAll('input, textarea, select').forEach(field => {
    field.addEventListener('blur', function() {
      const validation = validateField(this);
      if (!validation.valid) {
        this.style.borderColor = 'var(--error)';
      } else {
        this.style.borderColor = '';
      }
    });

    field.addEventListener('focus', function() {
      this.style.borderColor = '';
    });
  });

  // Form submission
  form.addEventListener('submit', async function(e) {
    e.preventDefault();

    // Validate all fields
    const fields = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    let errors = [];

    fields.forEach(field => {
      const validation = validateField(field);
      if (!validation.valid) {
        isValid = false;
        errors.push(validation.message);
        field.style.borderColor = 'var(--error)';
      }
    });

    if (!isValid) {
      showAlert(errors.join(' '), 'error');
      return;
    }

    // Prepare form data
    const formData = new FormData(form);
    const data = Object.fromEntries(formData);

    // Show loading state
    submitBtn.disabled = true;
    const originalText = submitBtn.innerHTML;
    submitBtn.innerHTML = '<span class="spinner"></span>送信中...';

    try {
      // Send to server
      const response = await fetch('contact-handler.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
      });

      const result = await response.json();

      if (result.success) {
        showAlert('お問合せありがとうございます。確認メールを送信いたしました。', 'success');
        form.reset();
        
        // Reset field styles
        form.querySelectorAll('input, textarea, select').forEach(field => {
          field.style.borderColor = '';
        });
      } else {
        showAlert(result.message || 'エラーが発生しました。もう一度お試しください。', 'error');
      }
    } catch (error) {
      console.error('Error:', error);
      showAlert('通信エラーが発生しました。もう一度お試しください。', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.innerHTML = originalText;
    }
  });

  // Email field validation
  const emailField = document.getElementById('email');
  if (emailField) {
    emailField.addEventListener('input', function() {
      if (this.value && !this.value.includes('@')) {
        this.style.borderColor = 'rgba(211,47,47,0.3)';
      } else {
        this.style.borderColor = '';
      }
    });
  }
});
