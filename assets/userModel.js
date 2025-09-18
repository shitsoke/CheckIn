// UserModel: Handles user data and session
class UserModel {
  static getUser() {
    const user = localStorage.getItem('user');
    return user ? JSON.parse(user) : null;
  }
  static setUser(user) {
    localStorage.setItem('user', JSON.stringify(user));
    document.cookie = `userType=${user.type}; path=/`;
  }
  static clearUser() {
    localStorage.removeItem('user');
    document.cookie = 'userType=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
  }
  static isAdmin() {
    const user = this.getUser();
    return user && user.type === 'admin';
  }
  static isCustomer() {
    const user = this.getUser();
    return user && user.type === 'customer';
  }
  static isLoggedIn() {
    return !!this.getUser();
  }
}

window.UserModel = UserModel;
