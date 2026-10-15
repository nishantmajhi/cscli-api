/* Unit 3: Input and Output
 * Reading and writing values of different types using formatted I/O
 * (scanf/printf) and conversion specifications. */
#include <stdio.h>

int main(void) {
    char name[50];
    int age;
    float height;

    printf("Enter your name, age, and height: ");
    scanf("%49s %d %f", name, &age, &height);

    printf("Name   : %s\n", name);
    printf("Age    : %d\n", age);
    printf("Height : %.2f\n", height);

    return 0;
}
