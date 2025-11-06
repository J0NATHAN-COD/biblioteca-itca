from flask import Flask, render_template, request, redirect, url_for, session, flash, jsonify
import mysql.connector
from config import get_db_connection
import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import io
import base64
from datetime import datetime, timedelta
import os

app = Flask(__name__)
app.secret_key = 'itca_biblioteca_secret_key_2024'

# Función para verificar login
def login_required(f):
    from functools import wraps
    @wraps(f)
    def decorated_function(*args, **kwargs):
        if 'loggedin' not in session:
            return redirect(url_for('login'))
        return f(*args, **kwargs)
    return decorated_function

# Página de inicio con ITCA FEPADE
@app.route('/')
def index():
    return render_template('index.html')

# Login
@app.route('/login', methods=['GET', 'POST'])
def login():
    if request.method == 'POST':
        username = request.form['username']
        password = request.form['password']
        
        conn = get_db_connection()
        if conn:
            cursor = conn.cursor(dictionary=True)
            cursor.execute('SELECT * FROM usuarios WHERE username = %s AND password = %s AND activo = TRUE', 
                         (username, password))
            user = cursor.fetchone()
            cursor.close()
            conn.close()
            
            if user:
                session['loggedin'] = True
                session['user_id'] = user['id']
                session['username'] = user['username']
                session['nombre'] = user['nombre']
                session['rol'] = user['rol']
                flash('¡Bienvenido ' + user['nombre'] + '!', 'success')
                return redirect(url_for('dashboard'))
            else:
                flash('Usuario o contraseña incorrectos', 'danger')
    
    return render_template('login.html')

# Logout
@app.route('/logout')
def logout():
    session.clear()
    flash('Has cerrado sesión exitosamente', 'info')
    return redirect(url_for('index'))

# Dashboard
@app.route('/dashboard')
@login_required
def dashboard():
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor(dictionary=True)
        
        # Estadísticas generales
        cursor.execute('SELECT COUNT(*) as total FROM libros')
        total_libros = cursor.fetchone()['total']
        
        cursor.execute('SELECT COUNT(*) as total FROM estudiantes WHERE activo = TRUE')
        total_estudiantes = cursor.fetchone()['total']
        
        cursor.execute('SELECT COUNT(*) as total FROM prestamos WHERE estado = "activo"')
        prestamos_activos = cursor.fetchone()['total']
        
        cursor.execute('''SELECT COUNT(*) as total FROM prestamos 
                         WHERE estado = "activo" AND fecha_devolucion_estimada < CURDATE()''')
        prestamos_atrasados = cursor.fetchone()['total']
        
        # Libros más prestados
        cursor.execute('''SELECT l.titulo, COUNT(p.id) as veces_prestado 
                         FROM prestamos p 
                         JOIN libros l ON p.libro_id = l.id 
                         GROUP BY l.id, l.titulo 
                         ORDER BY veces_prestado DESC 
                         LIMIT 5''')
        libros_populares = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return render_template('dashboard.html', 
                             total_libros=total_libros,
                             total_estudiantes=total_estudiantes,
                             prestamos_activos=prestamos_activos,
                             prestamos_atrasados=prestamos_atrasados,
                             libros_populares=libros_populares)
    return redirect(url_for('login'))

# Gestión de Libros
@app.route('/libros')
@login_required
def libros():
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor(dictionary=True)
        cursor.execute('SELECT * FROM libros ORDER BY titulo')
        libros = cursor.fetchall()
        cursor.close()
        conn.close()
        return render_template('libros.html', libros=libros)
    return redirect(url_for('login'))

# Agregar libro
@app.route('/agregar_libro', methods=['POST'])
@login_required
def agregar_libro():
    if request.method == 'POST':
        isbn = request.form['isbn']
        titulo = request.form['titulo']
        autor = request.form['autor']
        editorial = request.form['editorial']
        anio_publicacion = request.form['anio_publicacion']
        categoria = request.form['categoria']
        ejemplares = request.form['ejemplares']
        
        conn = get_db_connection()
        if conn:
            cursor = conn.cursor()
            try:
                cursor.execute('''INSERT INTO libros (isbn, titulo, autor, editorial, anio_publicacion, categoria, ejemplares, ejemplares_disponibles) 
                                VALUES (%s, %s, %s, %s, %s, %s, %s, %s)''',
                            (isbn, titulo, autor, editorial, anio_publicacion, categoria, ejemplares, ejemplares))
                conn.commit()
                flash('Libro agregado exitosamente', 'success')
            except mysql.connector.Error as err:
                flash(f'Error al agregar libro: {err}', 'danger')
            finally:
                cursor.close()
                conn.close()
    
    return redirect(url_for('libros'))

# Gestión de Estudiantes
@app.route('/estudiantes')
@login_required
def estudiantes():
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor(dictionary=True)
        cursor.execute('SELECT * FROM estudiantes WHERE activo = TRUE ORDER BY nombre')
        estudiantes = cursor.fetchall()
        cursor.close()
        conn.close()
        return render_template('estudiantes.html', estudiantes=estudiantes)
    return redirect(url_for('login'))

# Agregar estudiante
@app.route('/agregar_estudiante', methods=['POST'])
@login_required
def agregar_estudiante():
    if request.method == 'POST':
        carnet = request.form['carnet']
        dui = request.form['dui']
        nombre = request.form['nombre']
        email = request.form['email']
        telefono = request.form['telefono']
        carrera = request.form['carrera']
        
        conn = get_db_connection()
        if conn:
            cursor = conn.cursor()
            try:
                cursor.execute('''INSERT INTO estudiantes (carnet, dui, nombre, email, telefono, carrera) 
                                VALUES (%s, %s, %s, %s, %s, %s)''',
                            (carnet, dui, nombre, email, telefono, carrera))
                conn.commit()
                flash('Estudiante agregado exitosamente', 'success')
            except mysql.connector.Error as err:
                flash(f'Error al agregar estudiante: {err}', 'danger')
            finally:
                cursor.close()
                conn.close()
    
    return redirect(url_for('estudiantes'))

# Gestión de Préstamos
@app.route('/prestamos')
@login_required
def prestamos():
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor(dictionary=True)
        
        # Préstamos activos
        cursor.execute('''SELECT p.*, l.titulo, l.isbn, e.nombre as estudiante_nombre, e.carnet,
                         u.nombre as usuario_nombre, DATEDIFF(p.fecha_devolucion_estimada, CURDATE()) as dias_restantes
                         FROM prestamos p
                         JOIN libros l ON p.libro_id = l.id
                         JOIN estudiantes e ON p.estudiante_id = e.id
                         JOIN usuarios u ON p.usuario_id = u.id
                         WHERE p.estado = "activo"
                         ORDER BY p.fecha_prestamo DESC''')
        prestamos_activos = cursor.fetchall()
        
        # Libros disponibles
        cursor.execute('SELECT * FROM libros WHERE ejemplares_disponibles > 0 AND estado = "disponible"')
        libros_disponibles = cursor.fetchall()
        
        # Estudiantes activos
        cursor.execute('SELECT * FROM estudiantes WHERE activo = TRUE')
        estudiantes_activos = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        return render_template('prestamos.html', 
                             prestamos_activos=prestamos_activos,
                             libros_disponibles=libros_disponibles,
                             estudiantes_activos=estudiantes_activos)
    return redirect(url_for('login'))

# Realizar préstamo
@app.route('/realizar_prestamo', methods=['POST'])
@login_required
def realizar_prestamo():
    if request.method == 'POST':
        libro_id = request.form['libro_id']
        estudiante_id = request.form['estudiante_id']
        dias_prestamo = int(request.form['dias_prestamo'])
        
        conn = get_db_connection()
        if conn:
            cursor = conn.cursor()
            try:
                fecha_prestamo = datetime.now().date()
                fecha_devolucion = fecha_prestamo + timedelta(days=dias_prestamo)
                
                # Insertar préstamo
                cursor.execute('''INSERT INTO prestamos (libro_id, estudiante_id, usuario_id, fecha_prestamo, fecha_devolucion_estimada) 
                                VALUES (%s, %s, %s, %s, %s)''',
                            (libro_id, estudiante_id, session['user_id'], fecha_prestamo, fecha_devolucion))
                
                # Actualizar disponibilidad del libro
                cursor.execute('UPDATE libros SET ejemplares_disponibles = ejemplares_disponibles - 1 WHERE id = %s', (libro_id,))
                
                conn.commit()
                flash('Préstamo realizado exitosamente', 'success')
            except mysql.connector.Error as err:
                flash(f'Error al realizar préstamo: {err}', 'danger')
            finally:
                cursor.close()
                conn.close()
    
    return redirect(url_for('prestamos'))

# Devolver libro
@app.route('/devolver_libro/<int:prestamo_id>')
@login_required
def devolver_libro(prestamo_id):
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor(dictionary=True)
        try:
            # Obtener información del préstamo
            cursor.execute('SELECT libro_id FROM prestamos WHERE id = %s', (prestamo_id,))
            prestamo = cursor.fetchone()
            
            if prestamo:
                # Actualizar préstamo
                cursor.execute('''UPDATE prestamos SET estado = "devuelto", fecha_devolucion_real = CURDATE() 
                                WHERE id = %s''', (prestamo_id,))
                
                # Actualizar disponibilidad del libro
                cursor.execute('UPDATE libros SET ejemplares_disponibles = ejemplares_disponibles + 1 WHERE id = %s', 
                             (prestamo['libro_id'],))
                
                conn.commit()
                flash('Libro devuelto exitosamente', 'success')
        except mysql.connector.Error as err:
            flash(f'Error al devolver libro: {err}', 'danger')
        finally:
            cursor.close()
            conn.close()
    
    return redirect(url_for('prestamos'))

# Reportes y Gráficos
@app.route('/reportes')
@login_required
def reportes():
    conn = get_db_connection()
    if conn:
        cursor = conn.cursor(dictionary=True)
        
        # Datos para gráficos
        cursor.execute('''SELECT categoria, COUNT(*) as total 
                         FROM libros 
                         GROUP BY categoria''')
        libros_por_categoria = cursor.fetchall()
        
        cursor.execute('''SELECT MONTH(fecha_prestamo) as mes, COUNT(*) as total 
                         FROM prestamos 
                         WHERE YEAR(fecha_prestamo) = YEAR(CURDATE())
                         GROUP BY MONTH(fecha_prestamo)''')
        prestamos_por_mes = cursor.fetchall()
        
        cursor.execute('''SELECT e.carrera, COUNT(p.id) as total 
                         FROM prestamos p
                         JOIN estudiantes e ON p.estudiante_id = e.id
                         GROUP BY e.carrera''')
        prestamos_por_carrera = cursor.fetchall()
        
        cursor.close()
        conn.close()
        
        # Generar gráficos
        grafico_categorias = generar_grafico_categorias(libros_por_categoria)
        grafico_mensual = generar_grafico_mensual(prestamos_por_mes)
        grafico_carreras = generar_grafico_carreras(prestamos_por_carrera)
        
        return render_template('reportes.html',
                             grafico_categorias=grafico_categorias,
                             grafico_mensual=grafico_mensual,
                             grafico_carreras=grafico_carreras)
    return redirect(url_for('login'))

def generar_grafico_categorias(datos):
    categorias = [d['categoria'] for d in datos]
    totals = [d['total'] for d in datos]
    
    plt.figure(figsize=(10, 6))
    plt.bar(categorias, totals, color='#dc3545')
    plt.title('Libros por Categoría')
    plt.xticks(rotation=45)
    plt.tight_layout()
    
    img = io.BytesIO()
    plt.savefig(img, format='png')
    img.seek(0)
    plt.close()
    
    return base64.b64encode(img.getvalue()).decode()

def generar_grafico_mensual(datos):
    meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic']
    prestamos_mensuales = [0] * 12
    
    for d in datos:
        if 1 <= d['mes'] <= 12:
            prestamos_mensuales[d['mes'] - 1] = d['total']
    
    plt.figure(figsize=(10, 6))
    plt.plot(meses, prestamos_mensuales, marker='o', color='#dc3545', linewidth=2)
    plt.title('Préstamos Mensuales')
    plt.grid(True, alpha=0.3)
    plt.tight_layout()
    
    img = io.BytesIO()
    plt.savefig(img, format='png')
    img.seek(0)
    plt.close()
    
    return base64.b64encode(img.getvalue()).decode()

def generar_grafico_carreras(datos):
    carreras = [d['carrera'] for d in datos]
    totals = [d['total'] for d in datos]
    
    plt.figure(figsize=(8, 8))
    plt.pie(totals, labels=carreras, autopct='%1.1f%%', startangle=90)
    plt.title('Préstamos por Carrera')
    plt.tight_layout()
    
    img = io.BytesIO()
    plt.savefig(img, format='png')
    img.seek(0)
    plt.close()
    
    return base64.b64encode(img.getvalue()).decode()

if __name__ == '__main__':
    app.run(debug=True)