import { Component } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { AuthLayoutComponent } from '../auth-layout/auth-layout.component';
import { environment } from '../../../environments/environment';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [CommonModule, FormsModule, RouterLink, AuthLayoutComponent],
  templateUrl: './login.component.html',
  styleUrls: ['./login.component.scss']
})
export class LoginComponent {
  email = '';
  password = '';
  isLoading = false;
  errorMessage = '';

  constructor(private router: Router) { }

  async onLogin() {
    // Reset error
    this.errorMessage = '';

    // Validation
    if (!this.email || !this.password) {
      this.errorMessage = 'Please fill in all fields.';
      return;
    }

    this.isLoading = true;

    try {
      const response = await fetch(`${environment.apiUrl}/login.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          email: this.email,
          password: this.password
        })
      });

      const result = await response.json();
      console.log('Login response:', result);

      if (result.success) {
        // Store user data
        localStorage.setItem('user_id', result.data.user_id.toString());
        localStorage.setItem('username', result.data.username);
        localStorage.setItem('email', result.data.email);

        // Navigate to dashboard
        this.router.navigate(['/dashboard']);
      } else {
        this.errorMessage = result.message || 'Login failed. Please try again.';
      }
    } catch (error) {
      console.error('Login error:', error);
      this.errorMessage = 'Connection error. Please check your internet connection and try again. (' + environment.apiUrl + ')';
    } finally {
      this.isLoading = false;
    }
  }

  onRandomLogin() {
    this.isLoading = true;

    // Simulate API delay
    setTimeout(() => {
      const mockUser = {
        id: Math.floor(Math.random() * 1000) + 1,
        name: 'Random Developer ' + Math.floor(Math.random() * 100),
        email: `dev${Math.floor(Math.random() * 1000)}@example.com`,
        plan: 'premium',
        token: 'mock-token-' + Date.now()
      };

      console.log('🎲 Random Login:', mockUser);

      // Store in LocalStorage (mapping to app's expected keys)
      localStorage.setItem('user_id', mockUser.id.toString());
      localStorage.setItem('username', mockUser.name);
      localStorage.setItem('email', mockUser.email);
      localStorage.setItem('user_type', 'demo'); // Flag to prevent DB calls
      localStorage.setItem('plan', mockUser.plan); // Extra as requested
      localStorage.setItem('token', mockUser.token); // Extra as requested

      this.isLoading = false;
      this.router.navigate(['/dashboard']);
    }, 800);
  }
}
