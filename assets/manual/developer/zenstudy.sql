/**
 * Estrutura do banco de dados do sistema.
 *
 * Author:  mateus <mateushtoledo@gmail.com>
 * Created: 23/02/2019
 */

/*Criar banco de dados
/*CREATE DATABASE zenstudy;*/

/*Tabela de usuários*/
CREATE TABLE user (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(128) NOT NULL,
  email varchar(64) NOT NULL UNIQUE,
  password varchar(64) NOT NULL,
  authority enum('ADMIN','TEACHER','STUDENT') NOT NULL,
  active int(11) NOT NULL DEFAULT '0',
  gender enum('M','F') DEFAULT NULL
);

/*Tabela de dados de professores (complementa a de usuários)*/
CREATE TABLE teacher (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  instituition varchar(256) NOT NULL,
  academic_level enum('Ensino médio','Graduação','Mestrado','Doutorado','Pós Doutorado','Outros') DEFAULT 'Outros',
  lattes_link varchar(256) DEFAULT NULL,
  user_id int(11) NOT NULL UNIQUE,
  FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
);

/*Formulários de contato*/
CREATE TABLE contact (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name varchar(128) NOT NULL,
  email varchar(64) NOT NULL,
  subject varchar(128) NOT NULL,
  message varchar(512) NOT NULL,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
);

/*Tabela dos cursos*/
CREATE TABLE course (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  title varchar(128) NOT NULL,
  description TEXT NOT NULL,
  pre_requirements text,
  status enum('ACTIVE','LOCKED') DEFAULT 'LOCKED',
  banner varchar(128) NOT NULL,
  teacher_id int(11) NOT NULL,
  FOREIGN KEY (teacher_id) REFERENCES user (id) ON DELETE CASCADE
);

/*Aulas do curso*/
CREATE TABLE course_class (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  position int(11) DEFAULT '1',
  title varchar(128) NOT NULL,
  teoric_video varchar(128) DEFAULT NULL,
  pratic_video varchar(128) DEFAULT NULL,
  resum varchar(128) DEFAULT NULL,
  slide varchar(128) DEFAULT NULL,
  exercises varchar(128) DEFAULT NULL,
  attachment varchar(128) DEFAULT NULL,
  course_id int(11) NOT NULL,
  FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE
);

/*Links para materiais externos*/
CREATE TABLE class_links (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  link varchar(256) NOT NULL,
  class_id int(11) NOT NULL,
  FOREIGN KEY (class_id) REFERENCES course_class (id) ON DELETE CASCADE
);

/*Matrículas em cursos*/
CREATE TABLE course_registration (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  status enum('OPEN','FINISHED') DEFAULT 'OPEN',
  course_id int(11) NOT NULL,
  user_id int(11) NOT NULL,
  KEY user_id (user_id),
  KEY course_id (course_id),
  FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
  FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE
);

/*Conclusão de aula*/
CREATE TABLE class_conclusion(
    id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    course_id INT NOT NULL,
    class_id INT NOT NULL,
    user_id INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES course (id) ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES course_class (id) ON DELETE CASCADE
);

/*Recuperação de senha*/
CREATE TABLE pass_recovery (
  id int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  recovery_key varchar(64) NOT NULL UNIQUE,
  created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  user_id int(11) NOT NULL,
  FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
);

/*Tabela de avaliação dos cursos*/
create table course_evaluation(
	id INT AUTO_INCREMENT PRIMARY KEY,
	course_id INT NOT NULL,
	user_id INT NOT NULL,
        evaluation INT NOT NULL,
	FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE,
	FOREIGN KEY (course_id) REFERENCES course(id) ON DELETE CASCADE
);

/*Tabela de questões*/
create table question(
	id INT AUTO_INCREMENT PRIMARY KEY,
	question VARCHAR(128) NOT NULL
);

/*Tabela de opções para as questões*/
create table question_option(
	id INT AUTO_INCREMENT PRIMARY KEY,
	question_id INT NOT NULL,
	question_option VARCHAR(512) NOT NULL,
	FOREIGN KEY (question_id) REFERENCES question(id) ON DELETE CASCADE
);

/*Tabela de resposta para as questões*/
create table question_answer (
	id INT AUTO_INCREMENT PRIMARY KEY,
	question_id INT NOT NULL,
	option_id INT NOT NULL,
        user_id INT NOT NULL,
	FOREIGN KEY (question_id) REFERENCES question(id) ON DELETE CASCADE,
	FOREIGN KEY (option_id) REFERENCES question_option(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES user(id) ON DELETE CASCADE
);

/*DEFAULT DATA*/
/*questions (2)*/
INSERT INTO question(question) VALUES ('Qual era seu objetivo nesse curso?'),('Você atingiu o seu objetivo, especificado na questão anterior?');
/*question options*/
INSERT INTO question_option(question_id, question_option) VALUES ('1','Aprender o conteúdo todo, do zero.'), ('1','Aprender o conteúdo todo, para agregar aos meus conhecimentos atuais sobre o tema.'), ('1','Aprender apenas algum tópico do conteúdo.'), ('1','Conhecer como era estudar pelo sistema, e escolhi esse curso por acaso.'), ('2','Sim, parcialmente.'), ('2','Sim, totalmente.'), ('2','Não atingi.');

/*Trigggers*/
/*Concluir curso ao concluir todas as aulas*/
DELIMITER $

CREATE TRIGGER updateStatus AFTER INSERT ON class_conclusion FOR EACH ROW
BEGIN
	DECLARE cnt BIGINT;
	DECLARE leassons BIGINT;

	SET cnt = (SELECT COUNT(DISTINCT(class_id)) FROM class_conclusion WHERE user_id = NEW.user_id and course_id = NEW.course_id);
	SET leassons = (SELECT COUNT(*) FROM course_class WHERE course_id = NEW.course_id);

	IF (cnt = leassons) THEN
        UPDATE course_registration SET STATUS = 'FINISHED' WHERE user_id = NEW.user_id AND course_id = NEW.course_id;
  	END IF;
END$

DELIMITER ;